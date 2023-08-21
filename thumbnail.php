<?php
require_once('ThumbGet.php');
$thumb = new ThumbGet();

if (isset($_GET['id']) && strlen($_GET['id']) === 11) {//?id= exists and is 11 characters long
    $yt = $thumb->getVideoInformationFromDb($_GET['id']);

    if (!isset($yt['video_id'])) {//Nothing found in the DB for this video ID
        $thumb->show404Header();
    }

    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <title><?php echo $yt['title']; ?> YouTube Thumbnails - Thumbget</title>
        <meta name="description"
              content="YouTube thumbnails for the video <?php echo $yt['title']; ?> from <?php echo $yt['channel_title']; ?> - Thumbget">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta http-equiv="content-type" content="text/html;charset=UTF-8">
        <link rel="stylesheet" href="style.min.css">
    </head>
    <body>
    <div class="container">
        <h1 class="my-2"><?php echo $yt['title']; ?></h1>
        <h2 class="mb-2 fs-4"><?php echo $yt['channel_title']; ?></h2>
        <h4 class="mb-4 fs-6">Uploaded <?php echo date('F jS Y', strtotime($yt['uploaded_at'])); ?></h4>
        <div class="card mb-4 shadow-lg bg-light-subtle">
            <div class="card-body py-4 px-3">
                <p class="mb-1">Max size:</p>
                <img src="https://i.ytimg.com/vi/<?php echo $yt['video_id'] ?>/maxresdefault.jpg" class="img-fluid"
                     alt="<?php echo $yt['title']; ?> MAX">
                <p class="mt-3 mb-1">HQ size:</p>
                <img src="https://i.ytimg.com/vi/<?php echo $yt['video_id'] ?>/hqdefault.jpg" class="img-fluid"
                     alt="<?php echo $yt['title']; ?> HQ">
                <p class="mt-3 mb-1">MD size:</p>
                <img src="https://i.ytimg.com/vi/<?php echo $yt['video_id'] ?>/mqdefault.jpg" class="img-fluid"
                     alt="<?php echo $yt['title']; ?> MQ">
                <p class="mt-3 mb-1">SD size:</p>
                <img src="https://i.ytimg.com/vi/<?php echo $yt['video_id'] ?>/sddefault.jpg" class="img-fluid"
                     alt="<?php echo $yt['title']; ?> SD">
                <p class="mt-3 mb-1">Default size:</p>
                <img src="https://i.ytimg.com/vi/<?php echo $yt['video_id'] ?>/default.jpg" class="img-fluid"
                     alt="<?php echo $yt['title']; ?> DEFAULT">
            </div>
        </div>
    </div>
    </body>
    </html>
    <?php
} else {
    $thumb->show400Header();
}
