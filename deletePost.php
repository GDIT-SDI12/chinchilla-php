<?php

/**
 * This module DELETES post from database
 */
require_once 'commons/validatesession.php';
require_once 'commons/db.php';
require_once 'entity/User.php';
require_once 'entity/Post.php';
require_once 'dao/userDao.php';
require_once 'dao/postDao.php';

$user = new User();
$user = unserialize($_SESSION['user']);
$post = new Post();

$postDao = new PostDao();
$post = $postDao->find($_POST['postId']);
if ($user->getUsername() !== $post->getAuthor()) {
    http_response_code(403);
    exit;
}

$userDao = new UserDao();
$result = $postDao->delete($_POST['postId']);
if (!$result) {
    echo $result;
} else {
    $_SESSION['user'] = serialize($userDao->find($user->getUsername()));
    header("location: ./myposts.php");
}
