<?php
namespace App\Frontend\Modules\News;

use Entity\Comment;
use FormBuilder\CommentFormBuilder;
use OCFram\BackController;
use OCFram\CacheUpdater;
use OCFram\FormHandler;
use OCFram\HTTPRequest;

class NewsController extends BackController
{
  use CacheUpdater;

  public function executeIndex(HTTPRequest $request)
  {
    $nombreNews = $this->app->config()->get('nombre_news');
    $nombreCaracteres = $this->app->config()->get('nombre_caracteres');
    
    // On ajoute une définition pour le titre.
    $this->page->addVar('title', 'Liste des '.$nombreNews.' dernières news');
    
    // On récupère le manager des news.
    $manager = $this->managers->getManagerOf('News');
    
    $listeNews = $manager->getList(0, $nombreNews);
    
    // récupère le gestionnaire de cache data pour les news
    $handler = $this->app->getCacheHandlerOf('Data', 'News');

    foreach ($listeNews as $news)
    {
      // si le cache data n'existe pas (ou s'il a expiré) pour cette news, il sera créé
      if ($handler && is_null($handler->readCache($news->id())))
        $handler->createCache($news, $news->id());

      if (strlen($news->contenu()) > $nombreCaracteres)
      {
        $debut = substr($news->contenu(), 0, $nombreCaracteres);
        $debut = substr($debut, 0, strrpos($debut, ' ')) . '...';
        
        $news->setContenu($debut);
      }
    }
    
    // On ajoute la variable $listeNews à la vue.
    $this->page->addVar('listeNews', $listeNews);
  }
  
  public function executeShow(HTTPRequest $request)
  {
    // récupère la news depuis le cache (si possible) ou depuis la base de données
    $news = $this->getCacheOrLiveDataFor($this, 'News', $request->getData('id'));
    
    if (empty($news))
    {
      $this->app->httpResponse()->redirect404();
    }
    
    // récupère la liste des commentaire depuis le cache (si possible) ou depuis la base de données
    $comments = $this->getCacheOrLiveDataFor($this, 'Comment', $news->id());

    $this->page->addVar('title', $news->titre());
    $this->page->addVar('news', $news);
    $this->page->addVar('comments', $comments);
  }

  public function executeInsertComment(HTTPRequest $request)
  {
    // Si le formulaire a été envoyé.
    if ($request->method() == 'POST')
    {
      $comment = new Comment([
        'news' => $request->getData('news'),
        'auteur' => $request->postData('auteur'),
        'contenu' => $request->postData('contenu')
      ]);
    }
    else
    {
      $comment = new Comment;
    }

    $formBuilder = new CommentFormBuilder($comment);
    $formBuilder->build();

    $form = $formBuilder->form();

    $formHandler = new FormHandler($form, $this->managers->getManagerOf('Comments'), $request);

    if ($formHandler->process())
    {
      // supprime les différents caches liés à ce commentaire (s'ils existent et sont activés)
      $this->removeCommentCachesOf($this, $comment->id(), $comment->news());

      $this->app->user()->setFlash('Le commentaire a bien été ajouté, merci !');
      
      $this->app->httpResponse()->redirect('news-'.$request->getData('news').'.html');
    }

    $this->page->addVar('comment', $comment);
    $this->page->addVar('form', $form->createView());
    $this->page->addVar('title', 'Ajout d\'un commentaire');
  }
}