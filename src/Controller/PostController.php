<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use App\Entity\Post;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use App\Form\PostFormType;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use App\Entity\Image;




final class PostController extends AbstractController
{
    

    #[Route('/blog/new', name: 'new_post')]
    public function newPost(ManagerRegistry $doctrine, Request $request, SluggerInterface $slugger): Response
    {
        //If user is not logged it redirects to login.
        if (!$this->getUser()){
            return $this->redirectToRoute('app_login');
        }
        $post = new Post();
        $form = $this->createForm(PostFormType::class, $post);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $post = $form->getData();   
            
            $file = $form->get('image')->getData();

            if ($file) {
                $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();

                try {
                    // Primero lo movemos al directorio de imágenes
                    $file->move(
                        $this->getParameter('images_directory'), $newFilename
                    );
                    $filesystem = new Filesystem();
                    // Y ahora lo duplicamos en el directorio de portfolio
                    $filesystem->copy(
                        $this->getParameter('images_directory') . '/' . $newFilename,
                        $this->getParameter('blog_images_directory') . '/' . $newFilename, true);

                } catch (FileException $e) {
                    return new Response("Error al subir el archivo: " . $e->getMessage());
                }

                // Guardamos el nombre del archivo en el Post
                $post->setImage($newFilename);
            }
            // Quitamos los caracteres especiales del título para crear el slug
            $post->setSlug($slugger->slug($post->getTitle()));
            
            // Guardamos el usuario que crea el post y los contadores a 0
            $post->setPostUser($this->getUser());
            $post->setNumLikes(0);
            $post->setNumComments(0);

            $entityManager = $doctrine->getManager();    
            $entityManager->persist($post);
            $entityManager->flush();
            
            return $this->redirectToRoute('single_post', ["slug" => $post->getSlug()]);
        }
        return $this->render('blog/new_post.html.twig', array(
            'postForm' => $form->createView()    
        ));
    }
}
