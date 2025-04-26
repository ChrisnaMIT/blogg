<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\Comment;
use App\Form\CommentType;
use App\Form\PostType;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PostController extends AbstractController
{
    #[Route('/', name: 'app_posts')]
    public function index(PostRepository $postRepo): Response
    {
        return $this->render('post/index.html.twig', [
            'posts' => $postRepo->findAll(),
        ]);
    }


    #[Route('/post/{id}', name: 'app_post_show', priority: -1)]
    public function show(Post $post, Request $request, EntityManagerInterface $manager): Response
    {
        $comment = new Comment();
        $comment->setPost($post);
        $comment->setAuthor($this->getUser());
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $manager->persist($comment);
            $manager->flush();

            return $this->redirectToRoute('app_post_show', ['id' => $post->getId()]);

        }

        return $this->render('post/show.html.twig', [
            'post' => $post,
             'comments' => $post->getComments(),
             'form' => $form->createView(),
        ]);
    }

    #[Route('/post/create', name: 'app_post_create')]
    public function create(Request $request, EntityManagerInterface $manager):response
    {
        if(!$this->getUser())
        {
            return $this->redirectToRoute('app_login');
        }

        $post = new Post();
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {
            $post->setAuthor($this->getUser());
            $manager->persist($post);
            $manager->flush();
            return $this->redirectToRoute('app_posts', ['id' => $post->getId()]);
        }
        return $this->render('post/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('post/{id}/delete', name:'app_post_delete')]
    public function delete(Request $request, Post $post, EntityManagerInterface $manager): Response
    {
        foreach ($post->getComments() as $comment) {
            $manager->remove($comment);
        }
        $manager->remove($post);
        $manager->flush();

        if($post)
        {
            $manager->remove($post);
            $manager->flush();
        }
        return $this->redirectToRoute('app_posts');
    }

    #[Route('/post/{id}/edit', name:'app_post_edit')]
    public function edit(Request $request, Post $post, EntityManagerInterface $manager): Response
    {
        if(!$post){return $this->redirectToRoute('app_posts');}
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            $manager->persist($post);
            $manager->flush();
            return $this->redirectToRoute('app_posts');
        }
        return $this->render('post/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }


}
