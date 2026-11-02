<?php
namespace App\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use App\Entity\Post;
use App\Form\PostFormType;
final class PostController extends AbstractController
{
#[Route('/blog/new', name: 'new_post')]
public function newPost(ManagerRegistry $doctrine, Request $request, SluggerInterface $slugger): Response
{
if (!$this->getUser()) {
return $this->redirectToRoute('app_login');
}
$post = new Post();
$form = $this->createForm(PostFormType::class, $post);
$form->handleRequest($request);

if ($form->isSubmitted() && $form->isValid()) {
$imageFile = $form->get('image')->getData();

if ($imageFile) {
$originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
$safeFilename = $slugger->slug($originalFilename);
$newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

try {
                $imageFile->move(
                    $this->getParameter('blog_images_directory'),
$newFilename
);
} catch (FileException $e) {
}
$post->setImage($newFilename);
}
$post->setSlug($slugger->slug($post->getTitle()));
$post->setPostUser($this->getUser());
$post->setnumLikes(0);
$post->setnumComments(0);

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