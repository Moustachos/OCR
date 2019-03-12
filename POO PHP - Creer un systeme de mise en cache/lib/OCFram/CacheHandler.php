<?php
namespace OCFram;

use App\Frontend\Modules\News\NewsController;
use CacheHandler\DataCacheHandler;
use CacheHandler\ViewsCacheHandler;

abstract class CacheHandler extends ApplicationComponent
{
  /**
   * Attribut permettant de spécifier le nom de l'application pour laquelle on gère ce type de cache.
   * Si le fichier de configuration est chargé depuis le dossier de l'application en cours d'exécution, le nom sera identique.
   * Sinon, ce sera le nom de l'application par défaut.
   *
   * @see CacheHandler::getConfigFile() Pour le mode de chargement du fichier de configuration du cache.
   *
   * @var string Le nom de l'application
   */
  protected $appName = '';
  /**
   * @var string Le chemin (relatif) où seront stockés les fichiers de ce type de cache.
   */
  protected $path = '';
  /**
   * @var string L'extension (sans point) des fichiers de ce type de cache.
   */
  protected $fileExtension = '';
  /**
   * @var bool
   */
  protected $serializeCache = false;
  /**
   * Attribut permettant de spécifier si la clé des types d'éléments cachables doit être préfixée ou non avec le nom de l'application.
   * Exemple : le cache des vues est attendu sous la forme Nomdelapplication_Nomdumodule_Nomdelavue, il faut donc préfixer dans ce cas-là.
   *
   * @var bool
   */
  protected $prefixKeyWithAppName = false;
  /**
   * La liste des types d'éléments cachables chargés lors de l'initialisation du gestionnaire.
   *
   * @see CacheHandler::loadCacheableTypes()
   *
   * @var array La liste des types cachables. Chaque entrée est une instance de CacheableType.
   */
  protected $cacheableTypes = [];
  /**
   * Attribut permettant de stocker le dernier type d'élément cachable auquel on a voulu accéder avec Application::getCacheHandlerOf().
   * Cela permet d'éviter d'avoir à respecifier le type d'élément lors des appels à CacheHandler::readCache(), CacheHandler::createCache() etc.
   *
   * @see Application::getCacheHandlerOf()
   *
   * @var string Le dernier type d'élément cachable auquel on a accédé
   */
  protected $lastCacheType = '';

  /**
   * Constantes utilisées pour le formatage des clés de type d'éléments cachables ou pour le nom des fichiers de cache
   *
   * @see CacheHandler::formatString()
   */
  const FORMAT_PATH = 1;
  const FORMAT_KEY = 2;
  const FORMAT_FILENAME = 3;
  const SEPARATOR_KEY_OR_FILE = '_';
  const SEPARATOR_PATH = '/';
  const SEPARATOR_CACHED_ARRAY = '|||';
  const FILE_EXT_EXPIRED = 'expired';

  /**
   * Constantes utilisées pour la suppression des fichiers de cache
   *
   * @see CacheHandler::deleteFile()
   */
  const DELETE_METHOD_INSTANT = 1;       // supprime instantanément le fichier de cache $file
  const DELETE_METHOD_CLEAR_EXPIRED = 2; // supprime un éventuel fichier de cache expiré portant le même nom que $file

  public function __construct(Application $app, $appName, $path, $fileExtension, $serializeCache, $prefixKeyWithAppName, $elements)
  {
    parent::__construct($app);

    // charge les différents paramètres de ce type de cache et les types d'éléments cachables
    // si une erreur survient, une exception sera levée afin d'éviter de charger des paramètres incorrects
    $this->setAppName($appName);
    $this->setPath($path);
    $this->setFileExtension($fileExtension);
    $this->setSerializeCache($serializeCache);
    $this->setPrefixKeyWithAppName($prefixKeyWithAppName);
    $this->loadCacheableTypes($elements);
  }

  /**
   * Méthode permettant de récupérer le fichier de configuration cache.xml.
   * On recherche d'abord un fichier Config/cache.xml dans le dossier de l'application en cours.
   * S'il n'existe pas, on cherche le fichier cache.xml de l'application par défaut, spécifiée dans bootstrap.php (Frontend).
   *
   * @param Application $app L'application depuis laquelle on essaie de charger le fichier.
   * @param string &$appName Le nom de l'application pour laquelle un fichier de configuration de cache existe, ou null.
   * La valeur de ce paramètre peut être modifiée (par référence) lors de l'exécution de cette méthode.
   *
   * @return string|null Le chemin absolu vers le fichier cache.xml s'il existe, ou null.
   */
  public static function getConfigFile(Application $app, &$appName)
  {
    for ($i = 0; $i < 2; $i++)
    {
      // si le fichier de configuration du cache n'existe pas pour cette application (ex: Backend), on essaie de charger celui de l'application par défaut (définie dans bootstrap.php)
      $appName = ($i == 0) ? $app->name() : DEFAULT_APP;
      $file = __DIR__ . '/../../App/' .$appName.'/Config/cache.xml';

      if (file_exists($file))
        return $file;
    }

    $appName = null;
    return null;
  }

  // === MÉTHODES INTERNES DU CACHE ===

  /**
   * Méthode permettant de charger les types d'éléments cachables par ce gestionnaire de cache.
   * Exemple : News est un type d'élément cachable pour le gestionnaire de cache Data
   *
   * @param $elements La liste des éléments cachables à charger. Chaque entrée est une instance de DOMElement.
   *
   * @return void
   */
  protected function loadCacheableTypes($elements)
  {
    // chargement des élément autorisés, si une erreur survient, une exception sera levée
    foreach ($elements as $element)
      $this->setCacheableType($element);
  }

  /**
   * Méthode permettant d'assigner une clé à un type d'élément cachable pour l'insertion dans le tableau cacheableTypes.
   *
   * @param \DOMElement $element L'élément pour lequel on veut obtenir une clé.
   *
   * @see CacheHandler::setCacheableType()
   *
   * @return string La clé de ce type d'élément cachable.
   */
  protected function cacheableTypeKey(\DOMElement $element)
  {
    $attributesNames = [];

    foreach($element->attributes as $name => $node)
      if (strpos($name, 'data-') !== false)
        $attributesNames[] = $node->nodeValue;

    return $this->formatString(CacheHandler::FORMAT_KEY, null, array_values($attributesNames));
  }

  /**
   * Méthode permettant de formater une chaîne à partir des paramètres spécifiés.
   * Cette méthode est utilisée notamment pour formater la clé des types d'éléments cachables et le nom des fichiers de cache.
   *
   * @param $format int Le type de format de sortie souhaité.
   * @param $ids int|array Les identifiants de l'élément à ajouter à la chaîne. Peut être passé sous la forme d'une chaîne ou d'un tableau. Ignorés si null.
   * Exemple : dans le cas d'un appel pour le nom d'un fichier de cache de données, l'identifiant sera celui de l'entité à mettre en cache.
   * @param $args string|array Les arguments à utiliser pour le nom de la chaîne de sortie. Peut être passé sous la forme d'une chaîne ou d'un tableau.
   * Exemple : dans le cas d'un appel pour le nom d'un fichier de cache de vues, les arguments seront Nomdumodule_Nomdelavue ou [Nomdumodule, Nomdelavue].
   *
   * @see CacheHandler::FORMAT_PATH Pour les différents formats possibles.
   *
   * @return string La chaîne de caractères formatée selon les paramètres spécifiés.
   */
  protected function formatString($format, $ids, $args)
  {
    if (empty($args))
      throw new \InvalidArgumentException('Vous devez spécifier des arguments à formater');

    switch($format)
    {
      case CacheHandler::FORMAT_PATH:
        $separator = CacheHandler::SEPARATOR_PATH;
        break;
      default:
        $separator = CacheHandler::SEPARATOR_KEY_OR_FILE;
        break;
    }

    $output = '';

    // on ajoute le chemin (absolu) du dossier de cache si nécessaire
    if ($format == CacheHandler::FORMAT_FILENAME)
      $output .= $this->path;

    // on préfixe la chaîne avec le nom de l'application si nécessaire
    if ($this->prefixKeyWithAppName)
    {
      $prefix = false;

      // inutile de préfixer la chaîne si elle l'est déjà
      if (is_array($args))
        $prefix = !in_array($this->appName, $args);
      else if (is_string($args))
        $prefix = strpos($args, $this->appName) === false;

      if ($prefix)
        $output .= $this->appName().$separator;
    }

    // on ajoute les différents arguments
    if (is_array($args))
      $output .= implode($separator, $args);
    else
      $output .= $args;

    // on ajoute les ids de l'élément à mettre en cache à la chaîne si nécessaire
    if (!empty($ids))
    {
      if (is_array($ids))
        $output .= ($separator.implode($separator, $ids));
      else
        $output .= ($separator.$ids);
    }

    // on rajoute l'extension de fichier si nécessaire
    if ($format == CacheHandler::FORMAT_FILENAME && !empty($this->fileExtension))
      $output .= '.'.$this->fileExtension;

    return $output;
  }

  /**
   * Méthode permettant de vérifier que les paramètres de cache spécifiés par l'utilisateur sont conformes.
   * Une exception sera levée si ce n'est pas le cas.
   *
   * @param int|array|null $ids Les identifiants à vérifier. Peut être passé sous la forme d'une chaîne ou d'un tableau.
   * @param string|array|null &$type Le type d'élément cachable à vérifier. Peut être passé sous la forme d'une chaîne ou d'un tableau.
   * La valeur de ce paramètre peut être modifiée (par référence) lors de l'exécution de cette méthode.
   * @param bool $checkId Spécifie si l'on doit vérifier ou non la validité des identifiants spécifiés.
   *
   * @return void
   */
  protected function validateCacheParameters($ids = null, &$type = null, $checkId = false)
  {
    // si le type de cache n'a pas été spécifié, on essaie de récupérer le dernier type d'élément cachable utilisé
    $type = is_null($type) ? $this->lastCacheType() : $type;

    if (empty($type))
      throw new \InvalidArgumentException('Aucun type de cache spécifié');

    if (!$this->cacheable($type))
      throw new \InvalidArgumentException('Le type de cache spécifié est invalide');

    // si un identifiant est requis pour ce type d'élément cachable, on vérifie les identifiants spécifiés
    if ($checkId && !$this->validateIds($ids) && $this->requiresIdFor($type))
      throw new \InvalidArgumentException('Ce type de cache requiert un identifiant numérique non-nul');
  }

  /**
   * Méthode permettant de vérifier que les identifiants de cache spécifiés par l'utilisateur sont conformes.
   *
   * @param int|array|null $ids Les identifiants à vérifier. Peut être passé sous la forme d'une chaîne ou d'un tableau.
   *
   * @return bool
   */
  protected function validateIds($ids = null)
  {
    if (empty($ids))
      return false;

    if (is_array($ids))
    {
      foreach ($ids as $id)
        if (!$this->validateId($id))
          return false;

      return true;
    }

    return $this->validateId($ids);
  }

  /**
   * Méthode permettant de vérifier que l'identifiant de cache spécifié par l'utilisateur est conforme.
   *
   * @param int|null $id L'identifiant à vérifier.
   *
   * @return bool
   */
  protected function validateId($id = null)
  {
    return is_numeric($id) && $id > 0;
  }

  /**
   * Méthode permettant de vérifier si les paramètres spécifiés dans le fichier de configuration sont conformes pour un type d'élément cachable.
   * Cette méthode peut être implémentée dans les classes héritant de CacheHandler afin d'effectuer des vérifications plus spécifiques.
   *
   * @param \DOMElement $element L'élement de type de cache à vérifier.
   * @param \Exception &$exception L'exception qui pourra être levée dans cette méthode si  l'élément n'est pas conforme.
   * La valeur de ce paramètre peut être modifiée (par référence) lors de l'exécution de cette méthode.
   *
   * @see DataCacheHandler::isValidCacheableType()
   * @see ViewsCacheHandler::isValidCacheableType()
   *
   * @return bool
   */
  protected function isValidCacheableType(\DOMElement $element, &$exception) { return true; }

  // === HELPERS ((méthodes simplifiant certains appels / certaines vérifications courantes) ===

  /**
   * Méthode permettant de vérifier le type d'élément spécifié peut être mis en cache ou non.
   *
   * @param string $type Le type d'élément à vérifier.
   *
   * @return bool
   */
  public function cacheable($type)
  {
    // on préfixe la chaîne avec le nom de l'application si nécessaire
    if ($this->prefixKeyWithAppName && strpos($type, $this->appName) === false)
      $type = $this->appName.CacheHandler::SEPARATOR_KEY_OR_FILE.$type;

    return isset($this->cacheableTypes[$type]);
  }

  /**
   * Méthode permettant de réinitialiser le dernier type d'élément cachable auquel on a accédé.
   *
   * @see DataCacheHandler::$lastCacheType
   *
   * @return void
   */
  public function resetLastCacheType()
  {
    $this->setLastCacheType(null, true);
  }

  /**
   * Méthode permettant de récupérer le timestamp d'expiration pour le type d'élément cachable spécifié.
   * Si le type n'est pas cachable, une exception sera levée.
   *
   * @param string|null $type Le type d'élément cachable dont on souhaite obtenir la date d'expiration.
   *
   * @return int Le timestamp auquel ce type d'élément cachable expirera.
   */
  public function expirationTimeOf($type = null)
  {
    $this->validateCacheParameters(null, $type);
    return $this->cacheableTypes[$type]->expirationTime();
  }

  /**
   * Méthode permettant de vérifier si le type d'élément cachable spécifié requiert ou non un identifiant.
   *
   * @param string|null $type Le type d'élément cachable que l'on souhaite vérifier.
   *
   * @see CacheableType::$requiresId Pour plus d'informations.
   *
   * @return bool
   */
  public function requiresIdFor($type = null)
  {
    $this->validateCacheParameters(null, $type);
    return $this->cacheableTypes[$type]->requiresId();
  }

  /**
   * Méthode permettant de récupérer le nom de stockage (de fichier) pour le type d'élément cachable spécifié.
   * Exemple : l'entité Comment produit des fichiers commençant par Comments_of_News avec la configuration par défaut.
   *
   * @param string|null $type Le type d'élément cachable que l'on souhaite vérifier.
   *
   * @see CacheableType::$nameForFiles Pour plus d'informations.
   *
   * @return string|null Le nom de stockage (de fichier) pour ce type d'élément cachable.
   */
  public function nameForFilesOf($type = null)
  {
    $this->validateCacheParameters(null, $type);
    $nameForFiles = $this->cacheableTypes[$type]->nameForFiles();

    return !empty($nameForFiles) ? $nameForFiles : $type;
  }

  // === ACCESSEURS ===

  public function appName()
  {
    return $this->appName;
  }

  public function path()
  {
    return $this->path;
  }

  public function fileExtension()
  {
    return $this->fileExtension;
  }

  public function serializeCache()
  {
    return $this->serializeCache;
  }

  public function prefixKeyWithAppName()
  {
    return $this->prefixKeyWithAppName;
  }

  public function cacheableTypes()
  {
    return $this->cacheableTypes;
  }

  public function lastCacheType()
  {
    return $this->lastCacheType;
  }

  // === MUTATEURS ===

  protected function setAppName($appName)
  {
    if (!empty($appName) && is_string($appName))
      $this->appName = $appName;
    else
      throw new \InvalidArgumentException('Le nom de l\'application cible pour la mise en cache doit être une chaîne de caractères non-nulle');
  }

  protected function setPath($path)
  {
    if (!empty($path) && is_string($path))
      $this->path = __DIR__ . '/../../' .$path;
    else
      throw new \InvalidArgumentException('Le chemin du dossier de cache doit être une chaîne de caractères non-nulle');
  }

  protected function setFileExtension($fileExtension)
  {
    if (empty($fileExtension))
      throw new \InvalidArgumentException('Le format de fichier de cache doit être une chaîne de caractères non-nulle');

    if (ctype_alnum($fileExtension) === false)
      throw new \InvalidArgumentException('Le format de fichier de cache doit être uniquement composé de caractères alphanumériques');

    if (strcasecmp($fileExtension, CacheHandler::FILE_EXT_EXPIRED) == 0)
      throw new \InvalidArgumentException('Cette extension de fichier est réservée');

    $this->fileExtension = $fileExtension;
  }

  protected function setSerializeCache($serializeCache)
  {
    if (is_bool($serializeCache) === true)
      $this->serializeCache = $serializeCache;
    else
      throw new \InvalidArgumentException('Cette option doit être de type booléen');
  }

  protected function setPrefixKeyWithAppName($prefixKeyWithAppName)
  {
    if (is_bool($prefixKeyWithAppName) === true)
      $this->prefixKeyWithAppName = $prefixKeyWithAppName;
    else
      throw new \InvalidArgumentException('Cette option doit être de type booléen');
  }

  protected function setCacheableType(\DOMElement $element)
  {
    if ($this->isValidCacheableType($element, $exception))
      $this->cacheableTypes[$this->cacheableTypeKey($element)] = new CacheableType($element);
    else
      throw $exception;
  }

  public function setLastCacheType($type, $reset = false)
  {
    // seuls des types d'éléments cachables sont autorisés, sauf en cas de reset où null est une valeur acceptable
    if (($reset && is_null($type)) || $this->cacheable($type))
    {
      // on préfixe la chaîne avec le nom de l'application si nécessaire
      if ($this->prefixKeyWithAppName && strpos($type, $this->appName) === false)
        $type = $this->appName.CacheHandler::SEPARATOR_KEY_OR_FILE.$type;

      $this->lastCacheType = $type;
    }
  }

  // === MÉTHODES DE GESTION DU CACHE A PROPREMENT PARLER ===

  /**
   * Méthode permettant de récupérer le cache de l'élément spécifié.
   *
   * @param int|array|null $ids Les identifiants de l'élément à rechercher. Peut être passé sous la forme d'une chaîne ou d'un tableau.
   * @param string|array|null $type Le type d'élément cachable à rechercher. Peut être passé sous la forme d'une chaîne ou d'un tableau.
   *
   * @see NewsController::executeIndex() Pour un exemple d'appel à cette méthode.
   *
   * @return mixed|null La version en cache si elle existe et n'est pas expirée, ou null.
   */
  public function readCache($ids = null, $type = null)
  {
    $this->validateCacheParameters($ids, $type, true);
    $fileName = $this->formatString(CacheHandler::FORMAT_FILENAME, $ids, $this->nameForFilesOf($type));

    if (file_exists($fileName))
    {
      $file = file_get_contents($fileName);
      $cache = explode(PHP_EOL, $file);

      // le cache est expiré et doit être supprimé
      if ($cache[0] <= time())
      {
        // marque le fichier comme expiré, il sera supprimé pour de bon à la fin de CacheHandler::createCache()
        // la fonction unlink étant exécutée de façon asynchrone (sous Windows), cette suppression différée évite d'éventuels problèmes (liés au multithreading)
        // d'accès simultanés en lecture / écriture à un fichier en cours de suppression (cela peut se produire dans de très rares cas)
        $this->markFileAsExpired($fileName);
      }
      else
      {
        // supprime la date d'expiration
        unset($cache[0]);
        $content = is_array($cache) ? implode(PHP_EOL, $cache) : $cache;

        // désérialisation des données si nécessaire
        if ($this->serializeCache)
          $content = unserialize($content);

        // reconstruction du tableau si nécessaire
        if (is_string($content) && strpos($content, CacheHandler::SEPARATOR_CACHED_ARRAY) !== false)
          $content = explode(CacheHandler::SEPARATOR_CACHED_ARRAY, $content);

        return $content;
      }
    }

    return null;
  }

  /**
   * Méthode permettant de mettre en cache l'élément spécifié.
   *
   * @param $content Le contenu à mettre enregistrer dans le fichier de cache.
   * @param int|array|null $ids Les identifiants de l'élément à enregistrer. Peut être passé sous la forme d'une chaîne ou d'un tableau.
   * @param string|array|null $type Le type d'élément cachable à enregistrer. Peut être passé sous la forme d'une chaîne ou d'un tableau.
   *
   * @see NewsController::executeIndex() Pour un exemple d'appel à cette méthode.
   *
   * @return void
   */
  public function createCache($content, $ids = null, $type = null)
  {
    if (empty($content))
      return;

    $this->validateCacheParameters($ids, $type, true);
    $fileName = $this->formatString(CacheHandler::FORMAT_FILENAME, $ids, $this->nameForFilesOf($type));

    // sérialisation des données si nécessaire
    if ($this->serializeCache)
      $content = serialize($content);

    // transformation du tableau en chaîne si nécessaire
    if (is_array($content))
      $content = implode(CacheHandler::SEPARATOR_CACHED_ARRAY, $content);

    $cache = implode(PHP_EOL, [$this->expirationTimeOf($type), $content]);

    // création du dossier de destination s'il n'existe pas
    if (!is_dir($this->path))
      mkdir($this->path, 0777, true);

    // écriture des données puis suppression d'un éventuel fichier de cache expiré portant le même nom
    file_put_contents($fileName, $cache);
    $this->deleteFile($fileName, CacheHandler::DELETE_METHOD_CLEAR_EXPIRED);
  }

  /**
   * Méthode permettant de supprimer le cache de l'élément spécifié.
   *
   * @param int|array|null $ids Les identifiants de l'élément à supprimer. Peut être passé sous la forme d'une chaîne ou d'un tableau.
   * @param string|array|null $type Le type d'élément cachable à supprimer. Peut être passé sous la forme d'une chaîne ou d'un tableau.
   *
   * @see CacheUpdater::removeCacheOf() Pour un exemple d'appel à cette méthode.
   *
   * @return void
   */
  public function deleteCache($ids = null, $type = null)
  {
    $this->validateCacheParameters($ids, $type, true);
    $fileName = $this->formatString(CacheHandler::FORMAT_FILENAME, $ids, $this->nameForFilesOf($type));
    $this->deleteFile($fileName);
  }

  /**
   * Méthode permettant de supprimer un fichier de cache spécifique.
   *
   * @param $file Le fichier (avec chemin absolu) à supprimer.
   * @param int $method La méthode de suppression à employer.
   *
   * @see CacheHandler::DELETE_METHOD_INSTANT Pour plus d'informations sur les méthodes de suppression.
   *
   * @return void
   */
  protected function deleteFile($file, $method = CacheHandler::DELETE_METHOD_INSTANT)
  {
    if (is_file($file))
    {
      switch($method)
      {
        case CacheHandler::DELETE_METHOD_INSTANT:
          // supprime directement le fichier
          unlink($file);
          break;
        case CacheHandler::DELETE_METHOD_CLEAR_EXPIRED:
          $expired = $this->getExpiredFileName($file);

          // supprime le fichier expiré s'il existe
          if (file_exists($expired))
            unlink($expired);
          break;
        default:
          throw new \InvalidArgumentException('Cette méthode de suppression n\'existe pas');
      }
    }
  }

  /**
   * Méthode permettant de retourner le nom de fichier expiré pour $file.
   *
   * @param $file Le fichier de cache que l'on souhaite
   *
   * @return string Le nom de fichier expiré.
   */
  protected function getExpiredFileName($file)
  {
    return $file.'.'.CacheHandler::FILE_EXT_EXPIRED;
  }

  /**
   * Marque le fichier $file comme expiré, il sera supprimé dans un second temps.
   *
   * @see CacheHandler::readCache() Pour l'appel à cette méthode.
   *
   * @param $file Le fichier de cache à renommer.
   *
   * @return void
   */
  protected function markFileAsExpired($file)
  {
    rename($file, $this->getExpiredFileName($file));
  }

  /**
   * Méthode permettant de supprimer tous les fichiers de cache expirés de ce gestionnaire de cache.
   *
   * @return void
   */
  protected function clearExpiredCache()
  {
    $this->clearCache('*.'.CacheHandler::FILE_EXT_EXPIRED, false);
  }

  /**
   * Méthode permettant de supprimer tous les fichiers de cache de ce gestionnaire de cache.
   *
   * @param string $pattern Le type de fichiers à supprimer (tous, par défaut).
   * @param bool $removeDir Spécifie si l'on doit également supprimer le dossier de cache lui-même.
   *
   * @see CacheHandler::clearExpiredCache() Pour un appel à cette méthode avec un $pattern différent.
   *
   * @return void
   */
  protected function clearCache($pattern = '*', $removeDir = true)
  {
    if (is_dir($this->path))
    {
      array_map('unlink', glob($this->path.$pattern));

      if ($removeDir)
        rmdir($this->path);
    }
  }
}