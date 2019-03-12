<?php
namespace CacheHandler;

use OCFram\Application;
use \OCFram\CacheHandler;

class ViewsCacheHandler extends CacheHandler
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
      // vérifie que l'application à mettre en cache est valide
      if ($this->appName() == 'Backend')
        throw new \RuntimeException('Les vues de l\'espace d\'administration ne peuvent être mises en cache');

      // vérifie que la vue existe
      $file = __DIR__ . '../../../../App/'.$this->appName().'/Modules/'.$element->getAttribute('data-module').'/Views/'.$element->getAttribute('data-view').'.php';

      if (!file_exists($file))
        throw new \RuntimeException('La vue spécifiée à la ligne '.$element->getLineNo().' du fichier de configuration n\'existe pas');
    }
    catch (\Exception $e)
    {
      $exception = $e;
      return false;
    }

    return true;
  }

  /**
   * Méthode permettant d'envoyer le contenu d'une vue en cache au visiteur.
   *
   * @param $cachedView Le contenu de la vue en cache.
   *
   * @see Application::checkCachedView() Pour l'appel à cette méthode.
   *
   * @return void
   */
  public function sendCachedView($cachedView)
  {
    $user = $this->app->user();

    // si le contenu du cache est un tableau, c'est que le titre de la page a été stocké dans Page::getGeneratedPage()
    if (is_array($cachedView))
      list($content, $title) = $cachedView;
    else
      $content = $cachedView;

    // récupère le layout de l'application et envoie la page au visiteur
    ob_start();
    require $this->app->getApplicationLayout();
    $cachedView = ob_get_clean();
    $this->app->httpResponse()->send($cachedView);
  }
}