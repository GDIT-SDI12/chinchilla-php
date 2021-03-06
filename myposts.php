<?php
include_once './commons/validatesession.php';
include_once './commons/db.php';
include_once './entity/Post.php';
include_once './entity/User.php';
include_once './entity/Image.php';
include_once './dao/postDao.php';
include_once './dao/imageDao.php';

$user = new User();
$user = unserialize($_SESSION['user']);

$postFilter = new Post();
$typeFilter = "";
$sortFilter = "";
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
$postFilter->setAuthor($user->getUsername());
$myPosts = $postDao->list($postFilter);

if (!empty($_POST['CreateNewPost'])) {
    $author = new User();
    $author = unserialize($_SESSION['user']);
    if ($author == null) {
        header('location: logout.php');
        exit;
    }

    $newPost = new Post();
    $type = trim($_POST['postType']);
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);

    if (!empty($type)) {
        $newPost->setType($type);
    } else {
        header('location: myposts.php');
        exit;
    }
    if (!empty($title)) {
        $newPost->setTitle($title);
    } else {
        header('location: myposts.php');
        exit;
    }
    if (!empty($description)) {
        $newPost->setBody($description);
    } else {
        header('location: myposts.php');
        exit;
    }
    $newPost->setAuthor($author->getUsername());
    $newPost->setCreatedAt(date('Y-m-d H:i:s'));
    $newPost->setExpiredAt(date('Y-m-d H:i:s', strtotime($newPost->getCreatedAt() . '+ 30 days')));

    $postDao = new PostDao();
    $postId = $postDao->create($newPost);

    // upload image part
    // $target_dir = "uploads" . DIRECTORY_SEPARATOR . $author->getUsername() . DIRECTORY_SEPARATOR;
    $target_dir = getcwd() . DIRECTORY_SEPARATOR . "uploads" . DIRECTORY_SEPARATOR . $author->getUsername() . DIRECTORY_SEPARATOR;
    $target_name = $author->getUsername() . "_" . time() . "_1." . pathinfo(basename($_FILES["fileToUpload"]["name"]), PATHINFO_EXTENSION);
    $target_file = $target_dir . $target_name;
    echo $target_file;
    // create user directory if it doesn't exist
    if (!file_exists($target_dir)) {
        //echo $_SERVER["DOCUMENT_ROOT"] . '<br>';
        //echo getcwd();
        //$testdir = getcwd() . DIRECTORY_SEPARATOR . "uploads" . DIRECTORY_SEPARATOR . $author->getUsername();
        $testdir = getcwd() . DIRECTORY_SEPARATOR . "uploads" . DIRECTORY_SEPARATOR . $author->getUsername();
        echo $testdir;
        mkdir($testdir, 0777, true);
    }

    // Check if image file is a actual image or fake image
    $uploadFlag = 0;
    if (isset($_POST["CreateNewPost"])) {
        $check = getimagesize($_FILES["fileToUpload"]["tmp_name"]);
        if ($check !== false) {
            echo "File is an image - " . $check["mime"] . ".";
            $uploadFlag = 1;
        } else {
            echo "File is not an image.";
            $uploadFlag = 0;
        }
    }

    $imageFileType = strtolower(pathinfo($_FILES["fileToUpload"]["name"], PATHINFO_EXTENSION));

    // Allow certain file formats
    if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg") {
        echo "Sorry, only JPG, JPEG, & PNG files are allowed.";
        $uploadFlag = 0;
    }

    // Check if $uploadFlag is set to 0 by an error
    if ($uploadFlag == 0) {
        echo "Sorry, your file was not uploaded.";
    } else { // if everything is ok, try to upload file
        if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
            echo "The file " . basename($_FILES["fileToUpload"]["name"]) . " has been uploaded.";
            // insert to images table
            $image = new Image();
            $image->setPost($postId);
            $image->setFilename($author->getUsername() . "#DS#" . $target_name);
            $imageDao = new ImageDao();
            $imageDao->create($image);
        } else {
            echo "Sorry, there was an error uploading your file.";
        }
    }

    header('location: myposts.php');
    exit;
} else if (!empty($_POST['EditPost'])) {
    $id = trim($_POST['postId']);
    $type = trim($_POST['postType']);
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);

    $post = new Post();
    $post = $postDao->find($id);

    if(isset($post)) {
        $post->setType($type);
        $post->setTitle($title);
        $post->setBody($description);
        $postDao->update($post);
    }

    header('location: myposts.php');
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">

<?php require_once 'components/head.php' ?>

<body>
<?php require_once 'components/navbar.php' ?>
<div class="row pt-5">
    <div class="col-md-2">
        <ul class="nav nav-pills nav-fill flex-column">
            <li class="nav-item" style="margin-bottom: 1em;">
                <a class="nav-link btn-outline-primary" href="profile.php">Edit Profile</a>
            </li>
            <li class="nav-item" style="margin-bottom: 1em;">
                <a class="nav-link btn-outline-primary active" href="myposts.php">My Posts</a>
            </li>
            <li class="nav-item" style="margin-bottom: 1em;">
                <a class="nav-link btn-outline-primary" href="savedposts.php">Saved Posts</a>
            </li>
        </ul>
    </div>

    <div class="container col-md-10">
        <form action="myposts.php" method="GET" style="padding-bottom: 3em; padding-left: 15px; padding-right: 15px;">
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
                <div class="form-group col-md-1">
                    <button type="button" class="btn btn-primary btn-block" data-toggle="modal" 
                        data-target="#createPostModal">
                        Create Post
                    </button>
                </div>
            </div>
        </form>

        <!-- contents -->
        <?php foreach ($myPosts as $myPost) { ?>
            <div class="col-10 shadow">
                <div class="card mb-3">
                    <div class="row no-gutters">
                        <div class="col-md-4 p-2">
                            <?php if (null !== $myPost->getImages()) { ?>
                                <img src="<?= "uploads/" . str_replace("#DS#", DIRECTORY_SEPARATOR, array_values($myPost->getImages())[0]) ?>"
                                     class="card-img" alt="...">
                            <?php } else { ?>
                                <img src="http://via.placeholder.com/640x360" class="card-img" alt="...">
                            <?php } ?>
                        </div>
                        <div class="col-md-6">
                            <div class="card-body">
                                <h5 class="card-title"><?= $myPost->getTitle(); ?></h5>
                                <p class="card-text"><?= $myPost->getBody(); ?></p>
                            </div>
                        </div>

                        <div class="col-md-2">
                            <div class="card-body">
                                <?php if(null === $myPost->getApprovedAt()) { ?>
                                    <button type="button" class="btn btn-outline-primary btn-block"
                                        data-toggle="modal" data-target="#editPostModal" 
                                        data-id='<?= $myPost->getId() ?>'
                                        data-type='<?= $myPost->getType() ?>'
                                        data-title='<?= $myPost->getTitle() ?>'
                                        data-description='<?= $myPost->getBody() ?>'
                                        data-image='<?= $myPost->getImages()[0] ?>'
                                    >
                                        Edit
                                    </button>
                                <?php } else { ?>
                                    <button type="button" 
                                        class="btn btn-outline-primary btn-block" 
                                        style='pointer-events: none; text-decoration: line-through' 
                                        data-toggle="tooltip"
                                        data-placement="left"
                                        title="Cannot edit approved post!"
                                        disabled>
                                            Edit
                                    </button>
                                <?php } ?>

                                <?php include $myPost->isActive() ?  './commons/btnDisable.php' : './commons/btnEnable.php'; ?>
                                <button type="button" data-title="<?= $myPost->getTitle() ?>"
                                        data-id="<?= $myPost->getId() ?>" class="btn btn-outline-danger btn-block"
                                        data-toggle="modal" data-target="#deletePostModal">Delete
                                </button>
                            </div>
                        </div>

                        <div class="card-footer col" 
                            <?php if(null === $myPost->getApprovedAt()) { ?>
                                style="background-color: palegoldenrod;"
                            <?php } ?>
                        >
                            <p class="card-text text-right mr-3">
                                <small class="text-muted">
                                    <?php if(null === $myPost->getApprovedAt()) { ?>
                                        Pending Approval
                                    <?php } else { ?>
                                        Approved last <?= $myPost->getApprovedAt(); ?>
                                    <?php } ?>
                                </small>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        <?php } ?>
    </div>
</div>
<?php
include_once './components/createPostModal.php';
include_once './components/editPostModal.php';
include_once './components/deletePostModal.php';
?>
</body>
<script type="text/javascript" src="js/js.js"></script>
</html>
