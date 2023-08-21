<?php

class ThumbGet
{
    public string $video_id;
    public string $request;
    public int $request_length;

    private const YT_API_KEY = 'PUTYOURKEYHERE';

    private const BAD_REQUEST_FILE = 'thebadrequests';

    private const URL = 'https://thumbget.test/';

    public function setVideoId(string $video_id): string
    {
        return $this->video_id = $video_id;
    }

    public function setRequest(string $request): string
    {
        $this->request_length = strlen($request);
        return $this->request = $request;
    }

    private function db_connect(): PDO
    {
        $host = '127.0.0.1';
        $db_name = 'thumbget';
        $db_user = 'root';
        $db_password = '';
        return new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $db_user, $db_password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    }

    public function goToErrorPage(): void
    {
        header("Location: " . self::URL . "error.html");
        exit;
    }

    public function checkExists(): bool
    {
        $select = $this->db_connect()->prepare("SELECT `id` FROM `videos` WHERE `video_id` = ?;");
        $select->execute([$this->video_id]);
        return $select->rowCount() >= 1;
    }

    private function setVideoIdFromUrl(string $url): string
    {//Thanks to ghalusa
        preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $url, $match);
        return $this->setVideoId($match[1]);
    }

    private function insertVideoInformation(array $data): bool
    {
        $published_at = substr(str_replace("T", " ", $data['publishedAt']), 0, -1);

        $insert_video = $this->db_connect()->prepare("INSERT INTO `videos` (video_id, channel_id, uploaded_at, title, channel_title, inserted_at) VALUES (?, ?, ?, ?, ?, ?)");
        return $insert_video->execute([$this->video_id, $data['channelId'], $published_at, $data['title'], $data['channelTitle'], date('Y-m-d H:i:s')]);
    }

    private function callApi()
    {
        $do_call = @file_get_contents("https://www.googleapis.com/youtube/v3/videos?part=snippet&id={$this->video_id}&key=" . self::YT_API_KEY);

        if (strpos($http_response_header[0], "403") || strpos($http_response_header[0], "404") || strpos($http_response_header[0], "400")) {
            return false;
        }

        $data = json_decode($do_call, true);

        if ($data['pageInfo']['totalResults'] !== 0) {
            return $data['items'][0]['snippet'];
        }

        return false;
    }

    public function getVideoInformationFromDb(string $video_id)
    {
        $select = $this->db_connect()->prepare("SELECT * FROM `videos` WHERE `video_id` = ? LIMIT 1;");
        $select->execute([$video_id]);
        return $select->fetch(PDO::FETCH_ASSOC);
    }

    private function handleBadRequest(): void
    {//Logs and goes to error page
        $this->LogBadRequest($this->request);
        $this->goToErrorPage();
    }

    public function show404Header(): void
    {
        header("HTTP/1.1 404 Not Found");
        echo "<h1>404 not found</h1>";
        exit;
    }

    public function show400Header(): void
    {
        header("HTTP/1.1 400 BAD REQUEST");
        echo "<h1>400 bad request</h1>";
        exit;
    }

    private function LogBadRequest(string $bad_request): void
    {
        $fp = fopen(self::BAD_REQUEST_FILE . ".txt", 'ab') or exit("Unable to open txt file");
        file_put_contents(self::BAD_REQUEST_FILE . ".txt", $bad_request . "\n", FILE_APPEND);
    }

    public function processRequest(string $request): void
    {
        $this->setRequest($request);//Sets request and length of request

        if ($this->request_length >= 50) {//Not a valid URL so log it
            $this->handleBadRequest();
        }

        if ($this->request_length === 11) {//Video ID request
            $this->setVideoId($this->request);
        } elseif (str_contains($this->request, "youtube.com")) {//Video URL request
            $this->setVideoIdFromUrl($this->request);
        } else {
            $this->handleBadRequest();
        }

        if (!$this->checkExists()) {//Not in the DB

            $video_data = $this->callApi();//Fetch from YT API

            if (!$video_data) {//Error getting data
                $this->goToErrorPage();
            }

            $this->insertVideoInformation($video_data);
        }

        header("Location: thumbnail.php?id=" . $this->video_id);//Redirect to thumbnails
    }

    public function generateSitemap(): bool
    {
        $sitemap_file = 'sitemap.xml';
        if (file_exists($sitemap_file)) {//Exists so we must check last generated time

            $time_difference = time() - filemtime($sitemap_file);//Current time minus file last modified time
            $hours_difference = $time_difference / 3600;//Convert seconds to hours

            if ($hours_difference < 24) {//Sitemap last generated under 24 hours ago
                return false;
            }

        }

        $select_all = $this->db_connect()->query("SELECT `video_id`, `inserted_at` FROM `videos` ORDER BY `id`;");
        $pages = $select_all->fetchAll(PDO::FETCH_ASSOC);

        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>');

        $index = $xml->addChild('url');
        $index->addChild('loc', self::URL);
        $index->addChild('lastmod', '2023-08-20 10:00:00');
        $index->addChild('changefreq', 'monthly');
        $index->addChild('priority', '0.8');

        foreach ($pages as $page) {
            $element = $xml->addChild('url');
            $element->addChild('loc', self::URL . "thumbnail.php?id=" . $page['video_id']);
            $element->addChild('lastmod', $page['inserted_at']);
            $element->addChild('changefreq', 'monthly');
            $element->addChild('priority', '0.5');
        }

        $xml->asXML($sitemap_file);//Write sitemap to file

        return true;

    }

}