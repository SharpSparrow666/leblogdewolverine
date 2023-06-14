<?php

namespace App\Controller;

use App\Entity\Article;
use App\Form\EditPhotoFormType;
use Doctrine\Persistence\ManagerRegistry;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use mysql_xdevapi\RowResult;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class MainController extends AbstractController
{

    #[Route('/', name: 'main_home')]
    public function home(ManagerRegistry $doctrine,): Response
    {

        $articleRepo = $doctrine->getRepository(Article::class);

        $articles = $articleRepo->findBy([],['publicationDate' => 'DESC'], $this->getParameter('app.article.number_of_latest_articles_on_home'));

        return $this->render('main/home.html.twig', [
            'articles' => $articles,
        ]);
    }

    /**
     * Contrôleur de la page de profil (réservé aux personnes connectées
     */

    #[Route('/mon-profil/', name: 'main_profil')]
    #[IsGranted('ROLE_USER')]
    public function profil(): Response
    {
        return $this->render('main/profil.html.twig');
    }

//    Contrôleur de la page de modification de la photo de profil
//        Accès réservé aux utilisateurs connectés (ROLE_USER)

    #[Route('/changer-photo-de-profil/', name: 'main_edit_photo')]
    #[IsGranted('ROLE_USER')]
    public function editPhoto(Request $request, ManagerRegistry $doctrine, CacheManager $cacheManager): Response
    {

        $form = $this->createForm(EditPhotoFormType::class);

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){

        $photo = $form->get('photo')->getData();

        $connectedUser = $this->getUser();

        $photoLocation = $this->getParameter('app.user.photo.directory');

        $newFileName = 'user' . $connectedUser->getId() . '.' . $photo->guessExtension();

        if($connectedUser->getPhoto() != null && file_exists($photoLocation . $connectedUser->getPhoto())){
            $cacheManager->remove('images/profils/' . $connectedUser->getPhoto());
            unlink($photoLocation . $connectedUser->getPhoto());
        }

        $em = $doctrine->getManager();
        $connectedUser->setPhoto($newFileName);
        $em->flush();

        $photo->move(
            $photoLocation,
            $newFileName,
        );

        $this->addFlash('success', 'Photo de profil modifiée avec succès !');
        return $this->redirectToRoute('main_profil');

        }

        return $this->render('main/edit_photo.html.twig', [
            'edit_photo_form' => $form->createView(),
        ]);
    }

}



