<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Post;
use Doctrine\Persistence\ManagerRegistry;

final class BlogController extends AbstractController
{
   
    #[Route('/blog/{page}', name: 'blog')]
    public function blog(ManagerRegistry $doctrine, $page = 1): Response
    {
        $repositorio = $doctrine->getRepository(Post::class);
        $posts = $repositorio->findAll($page);
        $recents = $repositorio->findRecents();
        return $this->render('blog/blog.html.twig', [
            'posts' => $posts,
            'recents' => $recents,
        ]);
    }

   #[Route('/blog/single_post/{slug}', name: 'single_post')]
public function post(ManagerRegistry $doctrine, $slug): Response
{
    $repository = $doctrine->getRepository(Post::class);
    $post = $repository->findOneBy(["slug"=>$slug]);
    $recents = $repository->findRecents();
    return $this->render('blog/single_post.html.twig', [
        'post' => $post,
        'recents' => $recents
    ]);
}

}   
