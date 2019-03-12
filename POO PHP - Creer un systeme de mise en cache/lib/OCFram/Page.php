<?php
namespace OCFram;

class Page extends ApplicationComponent
{
  protected $contentFile;
  protected $vars = [];

  public function addVar($var, $value)
  {
    if (!is_string($var) || is_numeric($var) || empty($var))
    {
      throw new \InvalidArgumentException('Le nom de la variable doit être une chaine de caractères non nulle');
    }

    $this->vars[$var] = $value;
  }

  public function getGeneratedPage()
  {
    if (!file_exists($this->contentFile))
    {
      throw new \RuntimeException('La vue spécifiée n\'existe pas');
    }

    $user = $this->app->user();

    extract($this->vars);

    ob_start();
    require $this->contentFile;
    $content = ob_get_clean();

    // cette vue peut-être mise en cache: on l'enregistre
    if ($this->app->currentViewCacheable())
    {
      $viewType = implode(CacheHandler::SEPARATOR_KEY_OR_FILE, [$this->app->matchedRoute()->module(), $this->app->matchedRoute()->action()]);

      if ($handler = $this->app->getCacheHandlerOf('Views', $viewType, true))
      {
        $ids = array_values($this->app->matchedRoute()->vars());

        // si le titre de la page est disponible, on le stocke pour pouvoir l'afficher lors du chargement de la vue en cache
        $cache = isset($title) ? [$content, $title] : $content;
        $handler->createCache($cache, $ids);
      }
    }

    // récupère le layout de l'application et envoie la page au visiteur
    ob_start();
    require $this->app->getApplicationLayout();
    return ob_get_clean();
  }

  public function setContentFile($contentFile)
  {
    if (!is_string($contentFile) || empty($contentFile))
    {
      throw new \InvalidArgumentException('La vue spécifiée est invalide');
    }

    $this->contentFile = $contentFile;
  }
}