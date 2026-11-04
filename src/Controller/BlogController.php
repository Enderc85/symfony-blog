<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Post;
use App\Entity\Comment;
use App\Form\CommentFormType;
use Symfony\Component\HttpFoundation\JsonResponse;


final class BlogController extends AbstractController
{
private const POSTS_PER_PAGE = 5;

#[Route('/blog/{page}', name: 'blog', requirements: ['page' => '\d+'])]
public function index(ManagerRegistry $doctrine, int $page = 1): Response
{
$page = max(1, $page);

$repository = $doctrine->getRepository(Post::class);
// Aquí obtenemos los datos con el método creado en el repositorio
$posts = $repository->findPaginados($page, self::POSTS_PER_PAGE);
$recents = $repository->findRecents();

$total = count($posts);
$totalPages = max(1, (int) ceil($total / self::POSTS_PER_PAGE));

// Si piden una página que no existe, redirigimos a la última
if ($page > $totalPages) {
return $this->redirectToRoute('blog', ['page' => $totalPages]);
}

return $this->render('blog/blog.html.twig', [
'posts' => $posts,
'recents' => $recents,
'page' => $page,
'totalPages' => $totalPages,
'total' => $total,
]);
}
#[Route('/blog/buscar', name: 'blog_buscar', defaults: ['page' => 1])]
#[Route('/blog/buscar/{page}', name: 'blog_buscar', requirements: ['page' => '\d+'], defaults: ['page' => 1])]
public function buscar(ManagerRegistry $doctrine, Request $request, int $page = 1): Response
{
$page = max(1, $page);

$repository = $doctrine->getRepository(Post::class);
$searchTerm = $request->query->get('searchTerm', '');
$posts = $repository->findBuscadosPaginados($searchTerm, $page, self::POSTS_PER_PAGE);
$recents = $repository->findRecents();

$total = count($posts);
$totalPages = max(1, (int) ceil($total / self::POSTS_PER_PAGE));

if ($page > $totalPages) {
return $this->redirectToRoute('blog_buscar', ['page' => $totalPages, 'searchTerm' => $searchTerm]);
}

return $this->render('blog/blog.html.twig', [
'posts' => $posts,
'recents' => $recents,
'searchTerm' => $searchTerm,
'page' => $page,
'totalPages' => $totalPages,
'total' => $total,
]);
}

#[Route('/single_post/{slug}', name: 'single_post')]
public function post(ManagerRegistry $doctrine, Request $request, $slug): Response
{
$repository = $doctrine->getRepository(Post::class);
$post = $repository->findOneBy(["slug"=>$slug]);
$recents = $repository->findRecents();
$comment = new Comment();
$form = $this->createForm(CommentFormType::class, $comment);
$form->handleRequest($request);
if ($form->isSubmitted() && $form->isValid()) {
$comment = $form->getData();
$comment->setPost($post);
// Aumentamos en 1 el número de comentarios del post
$post->setNumComments($post->getNumComments() + 1);
$entityManager = $doctrine->getManager();
$entityManager->persist($comment);
$entityManager->flush();
return $this->redirectToRoute('single_post', ["slug" => $post->getSlug()]);
}
return $this->render('blog/single_post.html.twig', [
'post' => $post,
'recents' => $recents,
'commentForm' => $form->createView()
]);
}
#[Route('/single_post/{slug}/like', name: 'post_like')]
public function like(ManagerRegistry $doctrine, $slug): JsonResponse
{
$repository = $doctrine->getRepository(Post::class);
$post = $repository->findOneBy(["slug"=>$slug]);
if ($post){
$post->like();
$entityManager = $doctrine->getManager();
$entityManager->persist($post);
$entityManager->flush();

$data = ['success' => true, 'likes' => $post->getNumLikes()];
return $this->json($data);
}
return $this->json(['success' => false, 'error' => "Post no encontrado"], Response::HTTP_NOT_FOUND);

}

}