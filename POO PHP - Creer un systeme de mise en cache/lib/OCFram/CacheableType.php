<?php
namespace OCFram;

class CacheableType
{
  /**
   * Attribut spécifiant la durée de validité de ce type d'élément cachable.
   * Doit respecter la syntaxe des formats relatifs (http://fr2.php.net/manual/fr/datetime.formats.relative.php)
   *
   * @var string
   */
  protected $duration = '';
  /**
   * Attribut spécifiant si ce type d'élément cachable requiert ou non un identifant.
   * Exemple : les entités de type News ou Comment nécessitent un identifiant, la vue de type index du site n'en nécessite pas.
   *
   * @var bool
   */
  protected $requiresId = false;
  /**
   * Attribut permettant de spécifier un nom différent du type d'élément cachable pour le fichier dans lequel sera enregistré le cache.
   * S'il n'est pas spécifié, le fichier sera créé avec le nom du type d'élément cachable et la syntaxe spécifiée dans CacheHandler::formatString().
   *
   * @see CacheHandler::formatString()
   *
   * @var string
   */
  protected $nameForFiles = '';

  public function __construct(\DOMElement $element)
  {
    $requiresId = ($element->hasAttribute('requires-id') && $element->getAttribute('requires-id') == true);
    $nameForFiles = $element->hasAttribute('name-for-files') ? $element->getAttribute('name-for-files') : '';
    $this->setDuration($element->getAttribute('duration'), $element->getLineNo());
    $this->setRequiresId($requiresId);
    $this->setNameForFiles($nameForFiles);
  }

  // === ACCESSEURS ===

  public function expirationTime()
  {
    return strtotime($this->duration);
  }

  public function requiresId()
  {
    return $this->requiresId;
  }

  public function nameForFiles()
  {
    return $this->nameForFiles;
  }

  // === MUTATEURS ===

  private function setDuration($duration, $configLineNumber)
  {
    $time = strtotime($duration);

    /* vérifie que l'on obtient un timestamp avec la chaîne passée, renvoie false si:
      - la variable "duration" est absente du fichier de configuration
      - la chaîne de la variable "duration" n'est pas d'un format relatif valide (http://fr2.php.net/manual/fr/datetime.formats.relative.php)
    */
    if ($time === false)
      throw new \InvalidArgumentException('La chaîne de durée à la ligne '.$configLineNumber.' du fichier de configuration est invalide (nulle ou mal formatée)');

    // vérifie que le timestamp retourné correspond bien à une date future
    if ($time < time())
      throw new \InvalidArgumentException('La chaîne de durée à la ligne '.$configLineNumber.' du fichier de configuration produit une date d\'expiration dans le passé');

    $this->duration = $duration;
  }

  private function setRequiresId($requiredId)
  {
    if (!is_bool($requiredId))
      throw new \InvalidArgumentException('La valeur de requires-id doit être un booléen');

    $this->requiresId = $requiredId;
  }

  private function setNameForFiles($nameForFiles)
  {
    if (!empty($nameForFiles) && is_string($nameForFiles))
      $this->nameForFiles = $nameForFiles;
  }
}