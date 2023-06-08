<?php

namespace App\Controller;

use App\Entity\Article;
use App\Form\NewPublicationFormType;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
    #[Route('/nouvelle-publication/', name: 'new_publication')]
    #[IsGranted('ROLE_ADMIN')]
    public function newPublication(Request $request, ManagerRegistry $doctrine): Response
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

        return $this->render('blog/new_publication.html.twig', [
            'new_publication_form' => $form->createView(),
        ]);
    }

    // Contrôleur de la page qui liste les articles

    #[Route('/publications/liste/', name: 'publication_list')]
    public function publicationList(ManagerRegistry $doctrine): Response
    {

        $articleRepo = $doctrine->getRepository(Article::class);

        $articles = $articleRepo->findAll();

        return $this->render('blog/publication_list.html.twig', [
            'articles' => $articles,
        ]);
    }

    /**
     * Contrôleur de la page permettant de voir un article en détail
     */

    #[Route('/publication/{slug}/', name: 'publication_view')]
    public function publicationView(Article $article): Response
    {
        return $this->render('blog/publication_view.html.twig',[
            'article' => $article,
        ]);
    }
}

