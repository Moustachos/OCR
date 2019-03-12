<?php
namespace OCFram;

abstract class Application
{
  protected $httpRequest;
  protected $httpResponse;
  protected $name;
  protected $user;
  protected $config;
  protected $cacheHandlers;
  protected $matchedRoute;
  protected $currentViewCacheable;

  public function __construct()
  {
    $this->httpRequest = new HTTPRequest($this);
    $this->httpResponse = new HTTPResponse($this);
    $this->user = new User($this);
    $this->config = new Config($this);
    $this->currentViewCacheable = false;
  }

  /**
   * Méthode de chargement des différents types de caches.
   * Le script va d'abord essayer de charger un fichier Config/cache.xml dans le dossier de l'application en cours.
   * S'il n'existe pas, le script va essayer de charger le fichier cache.xml de l'application par défaut, spécifiée dans bootstrap.php (Frontend).
   *
   * Cela évite d'avoir à créer un fichier cache.xml dans le Backend pour manipuler le cache du Frontend.
   *
   * @see CacheHandler::getConfigFile() Pour le mode de chargement du fichier de configuration du cache.
   * @see Application::getController() Pour l'appel à cette méthode.
   *
   * @return void
   */
  private function loadCacheHandlers()
  {
    if ($file = CacheHandler::getConfigFile($this, $appName))
    {
      $xml = new \DOMDocument;
      $xml->load($file);

      foreach ($xml->getElementsByTagName('cache') as $cache)
      {
        $name = $cache->getAttribute('name');
        $path = $cache->getAttribute('path');
        $fileExtension = $cache->getAttribute('file-extension');
        $serializeCache = $cache->hasAttribute('serialize-cache') ? (bool) $cache->getAttribute('serialize-cache') : false;
        $prefixKeyWithAppName = $cache->hasAttribute('prefix-key-with-app-name') ? (bool) $cache->getAttribute('prefix-key-with-app-name') : false;

        // vérifie qu'il y a bien des éléments (ex: entités pour le cache datas, vues pour le cache views) à charger
        if ($cache->hasChildNodes())
        {
          $elements = [];

          // childNodes stocke également des nodes de type DOMText, on ajoute uniquement les éléments de type DOMElement au tableau
          foreach ($cache->childNodes as $child)
            if ($child instanceof \DOMElement)
              $elements[] = $child;

          // si des éléments ont bien été ajoutés au tableau, on instancie ce type de cache
          if (!empty($elements))
          {
            $cacheClass = '\CacheHandler\\'.$name.'CacheHandler';
            $this->cacheHandlers[$name] = new $cacheClass($this, $appName, $path, $fileExtension, $serializeCache, $prefixKeyWithAppName, $elements);
          }
        }
      }
    }
  }

  /**
   * Méthode permettant de vérifier si une version en cache existe pour la vue actuelle.
   *
   * Note: dans le fichier de configuration, j'ai laissé la possibilité de mettre en cache l'affichage d'une news en particulier.
   * Dans ce cas-là, il serait également préférable de vérifier si l'utilisateur en cours n'est pas authentifié,
   * afin d'éviter de stocker des informations sensibles en cache (ex: options réservées aux administrateurs).
   * Vous pouvez activer la mise en cache des vues uniquement pour les utilisateurs non-authentifiés
   * en décommentant la condition isAuthenticated() ci-dessous ;-)
   *
   * @see Application::getController() Pour l'appel à cette méthode.
   *
   * @return void
   */
  private function checkCachedView()
  {
    // vérifie que la mise en cache des vues est activée
    if (isset($this->cacheHandlers['Views']) /* && !$this->user->isAuthenticated() */)
    {
      $viewType = implode(CacheHandler::SEPARATOR_KEY_OR_FILE, [$this->matchedRoute->module(), $this->matchedRoute->action()]);

      // le cache vues est activé pour cette application (le paramètre strict a permis de s'en assurer)
      if ($handler = $this->getCacheHandlerOf('Views', $viewType, true))
      {
        // marque cette vue comme cachable (si cet attribut est vrai et que la vue n'est pas encore en cache, elle sera mise en cache dans Page::getGeneratedPage())
        $this->currentViewCacheable = true;
        $ids = array_values($this->matchedRoute->vars());

        // une version en cache est disponible pour cette vue: on l'affiche
        if ($cache = $handler->readCache($ids))
          $handler->sendCachedView($cache);
      }
    }
  }

  /**
   * Méthode de chargement des différentes routes.
   *
   * @see Application::getController() Pour l'appel à cette méthode.
   *
   * @return void
   */
  private function loadRoutes()
  {
    $router = new Router;

    $xml = new \DOMDocument;
    $xml->load(__DIR__.'/../../App/'.$this->name.'/Config/routes.xml');

    $routes = $xml->getElementsByTagName('route');

    // On parcourt les routes du fichier XML.
    foreach ($routes as $route)
    {
      $vars = [];

      // On regarde si des variables sont présentes dans l'URL.
      if ($route->hasAttribute('vars'))
      {
        $vars = explode(',', $route->getAttribute('vars'));
      }

      // On ajoute la route au routeur.
      $router->addRoute(new Route($route->getAttribute('url'), $route->getAttribute('module'), $route->getAttribute('action'), $vars));
    }

    try
    {
      // On récupère la route correspondante à l'URL.
      $this->matchedRoute = $router->getRoute($this->httpRequest->requestURI());
    }
    catch (\RuntimeException $e)
    {
      if ($e->getCode() == Router::NO_ROUTE)
      {
        // Si aucune route ne correspond, c'est que la page demandée n'existe pas.
        $this->httpResponse->redirect404();
      }
    }

    // On ajoute les variables de l'URL au tableau $_GET.
    $_GET = array_merge($_GET, $this->matchedRoute->vars());
  }

  public function getController()
  {
    // chargement des différents types de cache spécifiés dans cache.xml
    $this->loadCacheHandlers();

    // chargement des routes spécifiées dans routes.xml
    $this->loadRoutes();

    // vérifie si la vue actuelle est en cache ou si elle peut être mise en cache
    // si la vue existe en cache, la suite du code ne sera pas appelée
    $this->checkCachedView();

    // instancie le contrôleur.
    $controllerClass = 'App\\'.$this->name.'\\Modules\\'.$this->matchedRoute->module().'\\'.$this->matchedRoute->module().'Controller';
    return new $controllerClass($this, $this->matchedRoute->module(), $this->matchedRoute->action());
  }

  public function getApplicationLayout()
  {
    return __DIR__.'/../../App/'.$this->name().'/Templates/layout.php';
  }

  abstract public function run();

  public function httpRequest()
  {
    return $this->httpRequest;
  }

  public function httpResponse()
  {
    return $this->httpResponse;
  }

  public function name()
  {
    return $this->name;
  }

  public function config()
  {
    return $this->config;
  }

  public function user()
  {
    return $this->user;
  }

  public function cacheHandlers()
  {
    return $this->cacheHandlers;
  }

  public function matchedRoute()
  {
    return $this->matchedRoute;
  }

  /**
   * Méthode permettant de vérifier si la vue en cours peut-être mise en cache.
   * La valeur de retour est false par défaut, peut être passée à true dans Application::checkCachedView().
   *
   * @see Application::checkCachedView()
   *
   * @return bool
   */
  public function currentViewCacheable()
  {
    return $this->currentViewCacheable;
  }

  /**
   * Méthode permettant de retourner le gestionnaire de cache pour le cache $cacheType et l'élément $elementType.
   * Si le type de cache $cacheType n'existe pas (ou n'a pas été chargé), si l'élément $elementType ne peut être mis en cache : cette fonction renverra null.
   *
   * @param string $cacheType Le type de cache à récupérer (ex: Data).
   * @param string $elementType Le type d'élément de cache que l'on souhaite manipuler (ex: News). S'il est cachable, il sera stocké pour éviter d'avoir à le respécifier dans les appels suivants.
   * @param bool $strict Vérifie qu'on récupère bien le gestionnaire de l'application qui s'exécute actuellement et pas de celle par défaut.
   * Si on ne spécifie pas ce paramètre, on peut donc manipuler le cache du Frontend depuis le Backend.
   *
   * @see Application::loadCacheHandlers() Pour plus d'explications sur le mode de chargement du fichier cache.xml.
   *
   * @return CacheHandler|null Le gestionnaire de cache demandé.
   */
  public function getCacheHandlerOf($cacheType, $elementType, $strict = false)
  {
    if (isset($this->cacheHandlers[$cacheType]))
    {
      $handler = $this->cacheHandlers[$cacheType];

      if ($handler->cacheable($elementType) && (!$strict || $this->name == $handler->appName()))
      {
        $handler->setLastCacheType($elementType);
        return $handler;
      }
    }

    return null;
  }
}