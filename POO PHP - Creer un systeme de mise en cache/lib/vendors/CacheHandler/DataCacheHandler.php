<?php
namespace CacheHandler;

use \OCFram\CacheHandler;

class DataCacheHandler extends CacheHandler
{
  // === MÉTHODES INTERNES DU CACHE ===

  /**
   * Méthode héritée de CacheHandler permettant de vérifier si les paramètres spécifiés dans le fichier de configuration sont conformes pour un type d'élément cachable.
   *
   * @param \DOMElement $element L'élement de type de cache à vérifier.
   * @param \Exception &$exception L'exception qui pourra être levée dans cette méthode si  l'élément n'est pas conforme.
   * La valeur de ce paramètre peut être modifiée (par référence) lors de l'exécution de cette méthode.
   *
   * @see CacheHandler::isValidCacheableType() Pour plus d'informations
   *
   * @return bool
   */
  protected function isValidCacheableType(\DOMElement $element, &$exception)
  {
    try
    {
      // vérifie que l'on peut instancier un objet de ce type d'entité
      $entity = '\Entity\\'.$element->getAttribute('data-type');
      $obj = new $entity;
      unset($obj);
    }
    catch (\Exception $e)
    {
      $exception = $e;
      return false;
    }

    return true;
  }
}