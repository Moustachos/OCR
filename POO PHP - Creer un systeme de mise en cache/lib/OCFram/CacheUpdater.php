<?php
namespace OCFram;

trait CacheUpdater
{
  /**
   * Méthode permettant de retourner facilement la version en cache des données d'une entité ou les données actuelles (si le cache n'existe pas).
   *
   * @param ApplicationComponent $appComponent Une instance de la classe ApplicationComponent.
   * @param string $entity L'entité à rechercher.
   * @param int|null $id L'identifiant de l'entité à rechercher.
   *
   * @see NewsController::executeShow() Pour un exemple d'appel à cette méthode.
   *
   * @return mixed|null Le cache ou les données actuelles pour l'entité spécifiée, ou null si l'entité n'existe pas.
   */
  public function getCacheOrLiveDataFor(ApplicationComponent $appComponent, $entity, $id = null)
  {
   if (!is_numeric($id) || $id <= 0)
     throw new \InvalidArgumentException('Ce type de cache requiert un identifiant numérique non-nul');

    switch($entity)
    {
      case 'News':
        $managerName = $entity;
        $getMethodName = 'getUnique';
        break;
      case 'Comment':
        $managerName = 'Comments';
        $getMethodName = 'getListOf';
        break;
      default:
        throw new \InvalidArgumentException('Le type d\'entité "'.$entity.'" n\'est pas encore supporté dans cette méthode');
    }

    // vérifie que la mise en cache est disponible pour cette entité
    if ($handler = $appComponent->app()->getCacheHandlerOf('Data', $entity))
    {
      // créé un cache s'il n'existe pas déjà
      if (!($cache = $handler->readCache($id)))
      {
        $cache = $appComponent->managers->getManagerOf($managerName)->$getMethodName($id);
        $handler->createCache($cache, $id);
      }

      return $cache;
    }

    return $appComponent->managers->getManagerOf($managerName)->$getMethodName($id);
  }

  /**
   * Méthode permettant de supprimer facilement et manuellement le cache de l'élément spécifié.
   *
   * @param ApplicationComponent $appComponent Une instance de la classe ApplicationComponent.
   * @param string $cacheType Le type d'élément cachable à supprimer
   * @param string $elementType
   * @param int|array|null $ids Les identifiants de l'élément dont on veut supprimer le cache. Peut être passé sous la forme d'une chaîne ou d'un tableau.
   *
   * @see CacheUpdater::removeNewsCachesOf()
   * @see CacheUpdater::removeCommentCachesOf()
   *
   * @return void
   */
  public function removeCacheOf(ApplicationComponent $appComponent, $cacheType, $elementType, $ids = null)
  {
    // supprime le cache de cet élément si le cache en question est activé
    if ($handler = $appComponent->app()->getCacheHandlerOf($cacheType, $elementType))
      $handler->deleteCache($ids);
  }

  /**
   * Méthode permettant de supprimer facilement et manuellement le cache de la news spécifiée.
   *
   * @param ApplicationComponent $appComponent Une instance de la classe ApplicationComponent.
   * @param int $newsId L'identifiant de la news dont on veut supprimer le cache (données et vues).
   * @param bool $isNew Spécifie si la news vient d'être ajoutée ou non.
   *
   * @see NewsController::processForm() Pour un exemple d'appel à cette méthode.
   *
   * @return void
   */
  public function removeNewsCachesOf(ApplicationComponent $appComponent, $newsId, $isNew = false)
  {
    // si la news vient d'être ajoutée, on ne supprime que le cache de l'index du site (car la news devra y figurer)
    if ($isNew)
    {
      $this->removeCacheOf($appComponent, 'Views', 'Frontend_News_index');
      return;
    }

    // lance la suppression de tous les caches (données & vues) liés à cette news
    $this->removeCacheOf($appComponent, 'Data', 'News', $newsId);
    $this->removeCacheOf($appComponent, 'Data', 'Comment', $newsId);
    $this->removeCacheOf($appComponent, 'Views', 'News_index');
    $this->removeCacheOf($appComponent, 'Views', 'News_show', $newsId);
  }

  /**
   * Méthode permettant de supprimer facilement et manuellement le cache du commentaire spécifié.
   *
   * @param ApplicationComponent $appComponent Une instance de la classe ApplicationComponent.
   * @param int $commentId L'identifiant du commentaire dont on veut supprimer le cache (données et vues).
   * @param int|null $newsId L'identifiant de la news liée à ce commentaire.
   *
   * @see NewsController::executeUpdateComment() Pour un exemple d'appel à cette méthode.
   *
   * @return void
   */
  public function removeCommentCachesOf(ApplicationComponent $appComponent, $commentId, $newsId = null)
  {
    if (is_null($newsId))
      $newsId = $appComponent->managers->getManagerOf('Comments')->getNewsId($commentId);

    // lance la suppression de tous les caches (données & vues) liés à ce commentaire
    $this->removeCacheOf($appComponent, 'Data', 'Comment', $newsId);
    $this->removeCacheOf($appComponent, 'Views', 'Frontend_News_show', $newsId);
  }
}