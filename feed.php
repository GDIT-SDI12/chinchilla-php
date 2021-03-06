<?php
require_once './commons/validatesession.php';
include_once './commons/db.php';
include_once './entity/Post.php';
include_once './dao/postDao.php';

$postFilter = new Post();
$typeFilter = "";
$sortFilter = "";
$postFilter->setActive(true);
if ($_SERVER["REQUEST_METHOD"] == "GET") {

    if (isset($_GET["typeFilter"])) {
        $typeFilter = $_GET["typeFilter"];
    }

    if (isset($_GET["sortFilter"])) {
        $sortFilter = $_GET["sortFilter"];
    }

    if ($typeFilter == "job") {
        $postFilter->setType(1);
    } else if ($typeFilter == "rent") {
        $postFilter->setType(2);
    }

    if ($sortFilter == "asc") {
        $postFilter->setOrderBy($sortFilter);
    }
}

$postDao = new PostDao();
$posts = $postDao->list($postFilter);

?>
<!DOCTYPE html>
<html lang="en">

<?php require_once 'components/head.php' ?>

<body>
    <?php require_once 'components/navbar.php' ?>
    <div class="container">
        <form action="feed.php" method="GET" style="padding-top: 3em;">
            <div class="form-row">
                <div class="form-group col-md-3">
                    <select id="typeFilter" name="typeFilter" class="form-control">
                        <option value="all" <?= $typeFilter == "all" ? 'selected' : ''; ?>>
                            All (Default)
                        </option>
                        <option value="job" <?= $typeFilter == "job" ? 'selected' : ''; ?>>
                            Jobs
                        </option>
                        <option value="rent" <?= $typeFilter == "rent" ? 'selected' : ''; ?>>
                            Rent
                        </option>
                    </select>
                </div>
                <div class="form-group col-md-3">
                    <select id="sortFilter" name="sortFilter" class="form-control">
                        <option value="desc" <?= $sortFilter == "desc" ? 'selected' : ''; ?>>
                            Newest - Oldest (Descending)
                        </option>
                        <option value="asc" <?= $sortFilter == "asc" ? 'selected' : ''; ?>>
                            Oldest - Newest (Ascending)
                        </option>
                    </select>
                </div>
                <div class="form-group col-md-1">
                    <button type="submit" class="btn btn-primary btn-block">Filter</button>
                </div>
                    <!-- <button type="button" class="btn btn-primary ml-2" data-toggle="modal" data-target="#createPostModal">
                        Create Post
                    </button> -->
            </div>
        </form>
        <div class="row row-cols-1 row-cols-md-2" style="padding-top: 3em;">

            <?php foreach ($posts as $post) { ?>
                <div class="col mb-4">
                    <div class="card">
                            <?php $src = (null == $post->getImages()) ?
                                'http://via.placeholder.com/640x360' :
                                'uploads/' . str_replace("#DS#", '/', array_values($post->getImages())[0]); ?>
                            <a href="post.php?post=<?= $post->getId(); ?>">
                                <div class="p-3 preview" style="background-image: url('<?= $src ?>');"></div>
                            </a>
                        <div class='card-body <?= strtolower($post->getType()); ?>'>
                            <a href="post.php?post=<?= $post->getId(); ?>">
                                <h5 class="card-title"><?= $post->getTitle(); ?></h5>
                            </a>
                            <p class="card-text short-body"><?= $post->getBody(); ?></p>
                            <p class="card-text text-right"><small class="text-muted">Posted <?= $post->getCreatedAt(); ?></small></p>
                        </div>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>
</body>

</html>
<?php
include_once './components/createPostModal.php';
?>