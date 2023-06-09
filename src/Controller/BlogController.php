<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Comment;
use App\Form\CommentFormType;
use App\Form\NewPublicationFormType;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Préfixe de la route et du nom de toutes les pages de la partie blog du site
 */
#[Route('/blog', name: 'blog_')]
class BlogController extends AbstractController
{

    /**
     * Contrôleur de la page permettant de créer un nouvel article
     */
    #[Route('/nouvelle-publication/', name: 'publication_new')]
    #[IsGranted('ROLE_ADMIN')]
    public function publicationNew(Request $request, ManagerRegistry $doctrine): Response
    {
        // Création d'un nouvel Article vide
        $newArticle = new Article();

        $form = $this->createForm(NewPublicationFormType::class, $newArticle);

        //Liaison des données post au formulaire
        $form->handleRequest($request);

        // Si le formulaire sans erreur
        if ($form->isSubmitted() && $form->isValid()) {

            // Hydratation
            $newArticle
                ->setPublicationDate(new \DateTime())
                ->setAuthor($this->getUser());

            //Sauvegarde en BDD grâce au manager des entités
            $em = $doctrine->getManager();
            $em->persist($newArticle);
            $em->flush();

            //Message flash de succès
            $this->addFlash('success', 'Article publié avec succès !');

            return $this->redirectToRoute('blog_publication_view', [
                'slug' => $newArticle->getSlug(),
            ]);
        }

        return $this->render('blog/publication_new.html.twig', [
            'new_publication_form' => $form->createView(),
        ]);
    }

    // Contrôleur de la page qui liste les articles

    #[Route('/publications/liste/', name: 'publication_list')]
    public function publicationList(ManagerRegistry $doctrine, Request $request, PaginatorInterface $paginator): Response
    {
        $requestedPage = $request->query->getInt('page', 1);

        if ($requestedPage < 1) {

            throw new NotFoundHttpException();

        }

        $em = $doctrine->getManager();

        $query = $em->createQuery('SELECT a FROM App\Entity\Article a ORDER BY a.publicationDate DESC');

        $articles = $paginator->paginate(
            $query,
            $requestedPage,
            10
        );

        return $this->render('blog/publication_list.html.twig', [
            'articles' => $articles,
        ]);
    }

    /**
     * Contrôleur de la page permettant de voir un article en détail
     */

    #[Route('/publication/{slug}/', name: 'publication_view')]
    public function publicationView(Article $article, Request $request, ManagerRegistry $doctrine): Response
    {
//        Si l'utilisateur n'est pas connecté, on appelle la vue en bloquant la suite du chargement du contrôleur

        if(!$this->getUser()){
            return $this->render('blog/publication_view.html.twig', [
                'article' => $article,
            ]);
        }

        $comment = new Comment();

        $form = $this->createForm(CommentFormType::class, $comment);

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){

            $comment
                ->setPublicationDate(new \DateTime())
                ->setAuthor($this->getUser())
                ->setArticle($article)

                ;

            $em = $doctrine->getManager();
            $em->persist($comment);
            $em->flush();

            unset($comment);
            unset($form);

            $comment = new Comment();
            $form = $this->createForm(CommentFormType::class, $comment);

            $this->addFlash('success', 'Votre commentaire a été publié avec succès !');

        }

        return $this->render('blog/publication_view.html.twig', [
            'article' => $article,
            'comment_create_form' => $form->createView(),
        ]);
    }



//    Contrôleur de la page admin servant à supprimer un article via son id passé dans l'URL

    #[Route('/publication/suppression/{id}/', name: 'publication_delete', priority: 10)]
    #[IsGranted('ROLE_ADMIN')]
    public function publicationDelete(Article $article, ManagerRegistry $doctrine, Request $request): Response
    {

        // Vérification si token valide
        if (!$this->isCsrfTokenValid('blog_publication_delete_' . $article->getId(), $request->query->get('csrf_token'))) {

            $this->addFlash('error', 'Token sécurité invalide, veuillez ré-essayer.');
        } else {

            $em = $doctrine->getManager();
            $em->remove($article);
            $em->flush();

            $this->addFlash('success', 'La publication a été supprimée avec succès !');
        }

        return $this->redirectToRoute('blog_publication_list');

    }


// Contrôleur de la page admin servant à modifier un artciel existant via son id passé dans l'URL

    #[Route('/publication/modifier/{id}/', name: 'publication_edit', priority: 10)]
    #[IsGranted('ROLE_ADMIN')]
    public function publicationEdit(Article $article, Request $request, ManagerRegistry $doctrine): Response
    {
        $form = $this->createForm(NewPublicationFormType::class, $article);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){

            $em = $doctrine->getManager();
            $em->flush();

            $this->addFlash('success','Publication modifiée avec succès !');

            return $this->redirectToRoute('blog_publication_view', [
                'slug' => $article->getSlug(),
            ]);

        }

        return $this->render('blog/publication_edit.html.twig',[
            'edit_form' => $form->createView(),
        ]);
    }


// Contrôleur de la page permettant aux admins de supprimer un commentaire (réservé aux admins)
    #[Route('/commentaires/suppression{id}/', name: 'comment_delete')]
    #[IsGranted('ROLE_ADMIN')]
    public function commentDelete(Comment $comment, Request $request, ManagerRegistry $doctrine): Response
    {
        if(!$this->isCsrfTokenValid('blog_comment_delete_' . $comment->getId(), $request->query->get('csrf_token'))){

            $this->addFlash('error', 'Token sécurité invalide, veuillez ré-essayer.');

        } else {

            $em = $doctrine->getManager();
            $em->remove($comment);
            $em->flush();

            $this->addFlash('success', 'Le commentaire à été supprimé avec succès !');

        }

        return $this->redirectToRoute('blog_publication_view', [
            'slug' => $comment->getArticle()->getSlug(),
        ]);

    }

}

