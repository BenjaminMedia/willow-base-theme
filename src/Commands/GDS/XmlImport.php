<?php

namespace Bonnier\Willow\Base\Commands\GDS;

use Bonnier\Willow\Base\Helpers\HtmlToMarkdown;
use Bonnier\Willow\Base\Models\ACF\Attachment\AttachmentFieldGroup;
use Bonnier\Willow\Base\Models\WpAttachment;
use Bonnier\Willow\Base\Models\WpAuthor;
use WP_CLI;
use WP_CLI_Command;
use WP_User;

class XmlImport extends WP_CLI_Command
{
    private const CMD_NAMESPACE = 'gds';
    private $multiple;
    private $dir;
    private $log;
    private $skipImageUpload;
    private $exampleImageId;
    private $startContent;
    private $outputNextId;
    private $outputParsedData;
    private $force;
    private $encodeUtf8;
    private $outputOverview;

    public static function register()
    {
        WP_CLI::add_command(static::CMD_NAMESPACE, __CLASS__);
    }

    /**
     * Imports from xml from X-Cago
     *
     * ## EXAMPLES
     *
     * wp gds xmlimport ../xcago
     *
     */
    public function xmlimport($params)
    {
        $this->multiple = false;
        $this->log = false;
        $this->skipImageUpload = false;
        $this->startContent = false;
        $this->outputNextId = false;
        $this->outputParsedData = false;
        $this->force = false;
        $this->encodeUtf8 = false;
        $this->outputOverview = false;

        $remainingArguments = $this->parseArguments($params);
        if (sizeof($remainingArguments) > 0) {
            var_dump($remainingArguments);
            WP_CLI::error('Arguments not parsed. Maybe you misspelled?');
        }

        $this->log('Script start');

        if (!$this->dir) {
            WP_CLI::line('XmlImport parameters');
            WP_CLI::line('');
            WP_CLI::line('dir:/path/to/file the directory to import from');
            WP_CLI::line('This directory should contain directories that contain a html-file and jpg-files.');
            WP_CLI::line('');
            WP_CLI::line('"log:/path/to/file" if you want to log start and end timestamps of the import.');
            WP_CLI::line('');
            WP_CLI::line('"import-start-content/path/to/file" overwrite the current composite_content with the');
            WP_CLI::line('content of the file, before importing.');
            WP_CLI::line('');
            WP_CLI::line('"skip-image-upload" do not upload images to Wordpress and S3. Use a default picture instead.');
            WP_CLI::line('');
            WP_CLI::line('"output-next-id" show the next post ID to be imported.');
            WP_CLI::line('');
            WP_CLI::line('"output-parsed-data" show the data parsed in the xml file.');
            WP_CLI::line('');
            WP_CLI::line('"multiple" do not stop after the first file is imported.');
            WP_CLI::line('');
            WP_CLI::line('Examples:');
            WP_CLI::line('wp gds xmlimport dir:../folder-with-folders/');
            WP_CLI::line('wp gds xmlimport dir:../folder-with-folders/ log:/tmp/xmlimport');
            WP_CLI::line('wp gds xmlimport dir:../folder-with-folders/ log:/tmp/xmlimport multiple');
            WP_CLI::line('wp gds xmlimport dir:../folder-with-folders/ output-parsed-data');
            WP_CLI::line('wp gds xmlimport dir:../folder-with-folders/ skip-image-upload');
            WP_CLI::line('wp gds xmlimport dir:../folder-with-folders/ output-next-id');
            WP_CLI::line('');
            WP_CLI::error('Dir Argument is missing');
        }

        if (!is_dir($this->dir)) {
            WP_CLI::error('Invalid directory in argument: ' . $this->dir);
        }

        // Get dirs and iterate them
        $files = scandir($this->dir);
        $files = array_diff($files, ['..', '.']);
        foreach ($files as $file) {
            if (is_dir($this->dir . '/' . $file)) {
                $xmlParser = new XmlParser($this->dir, $file, $this->encodeUtf8);

                $blocks = $xmlParser->getBlocks();
                $postId = $xmlParser->getPostId();
                $materials = $xmlParser->getMaterials();
                $time = $xmlParser->getTime();
                $price = $xmlParser->getPrice();
                $difficulty = $xmlParser->getDifficultyText();

                if ($this->outputParsedData) {
                    WP_CLI::line('Outputting parsed data:');
                    var_export($xmlParser->getBlocks());
                    WP_CLI::line('Parsed data outputted');
                    WP_CLI::success('Done.');
                    exit;
                }

                if ($this->outputNextId) {
                    WP_CLI::line('');
                    WP_CLI::line('Next post ID: ' . $postId);
                    exit;
                }

                // Check if import has already been done for this post
                // Use "force" parameter to force reimport
                if (get_field('xml_import', $postId) && !$this->force) {
                    WP_CLI::error('This post has already been enriched with xml import. You can run it again with the "force" parameter.');
                }

                // Log start
                $this->log('Import - ' . $file . ' - start');

                if ($xmlParser->isGuide()) {
                    update_field('_wp_page_template', 'guide', $postId);  // TODO

                    // Save meta time
                    //WP_CLI::line('Save meta time: ' . $time);
                    update_field('field_5f560d8c95208', $time, $postId);

                    // Save meta price
                    //WP_CLI::line('Save meta price: ' . $price);
                    update_field('field_5f560ec795209', $price, $postId);
                }

                // Set author to the default editor
                self::setDefaultAuthor($xmlParser->getLanguage(), $postId);

                // Build composite content and save
                $compositeContent = $this->build($postId, $blocks, $this->dir . '/' . $file, $materials, $time, $price, $difficulty, $xmlParser->getLanguage());
                update_field('composite_content', $compositeContent, $postId);

                // Save import timestamp in meta
                update_field('xml_import', date('Y-m-d H:i:s'), $postId);

                // Log end
                $this->log('Import - ' . $file . ' - end');

                if (!$this->multiple) {
                    WP_CLI::line('"multiple" argument not set, so stopping after one import.');
                    WP_CLI::line('Post ID: ' . $postId);
                    WP_CLI::success('Done.');
                    exit;
                }
            }
        }

        print "Done.";
        $this->log('Script end');
        WP_CLI::success('Import done');
    }

    private function parseArguments($params)
    {
        $returnParams = [];
        foreach ($params as $param) {
            if ($param === 'multiple') {
                $this->multiple = true;
                continue;
            }
            if (substr($param, 0, 4) === 'dir:') {  // The basedir that includes the dirs to be imported
                $this->dir = substr($param, 4, strlen($param) - 4);
                continue;
            }
            if (substr($param, 0, 4) === 'log:') {  // Path to log-file
                $this->log = substr($param, 4, strlen($param) - 4);
                continue;
            }
            if (substr($param, 0, 21) === 'import-start-content:') {
                $startContentFileName = substr($param, 21, strlen($param) - 21);
                $this->importStartContent($startContentFileName);
                $this->multiple = false;
                continue;
            }
            if ($param === 'skip-image-upload') { // Skip upload of images (great for testing / debugging)
                $this->skipImageUpload = true;
                continue;
            }
            if ($param === 'output-next-id') {
                $this->outputNextId = true;
                continue;
            }
            if ($param === 'output-parsed-data') {
                $this->outputParsedData = true;
                continue;
            }
            if ($param === 'force') {
                $this->force = true;
                continue;
            }
            if ($param === 'encode-utf8' || $param === 'encode-utf-8') {
                $this->encodeUtf8 = true;
                continue;
            }
            if ($param === 'output-overview') {
                $this->outputOverview = true;
                $this->skipImageUpload = true;
                continue;
            }
            $returnParams[] = $param;
        }
        return $returnParams;
    }

    private function importStartContent($startContentFileName)
    {
        if (!is_file($startContentFileName)) {
            WP_CLI::error('Not a file: ' . $startContentFileName);
        }
        $startContent = file_get_contents($startContentFileName);
        eval("\$this->startContent = " . $startContent . ";");
        if (!$this->startContent) {
            WP_CLI::error('Error in importing start-content from: ' . $startContentFileName);
        }
    }

    // Export the post's CompositeContent to a file before importing
    private static function exportOriginalContent($dir, $compositeContent, $postId)
    {
        // Export array
        $file = $dir . '/original_content_' . $postId . '.txt';
        if (!file_exists($file)) {
            file_put_contents($file, var_export($compositeContent, true));
            WP_CLI::line('Export of postId: ' . $postId);
            WP_CLI::line('Exported to: ' . $file);
            WP_CLI::line('Filesize: ' . filesize($file));
        }

        // Export json
        $file = $dir . '/original_content_' . $postId . '.json';
        if (!file_exists($file)) {
            file_put_contents($file, json_encode($compositeContent));
            WP_CLI::line('Exported to: ' . $file);
            WP_CLI::line('Filesize: ' . filesize($file));
        }
    }

    private static function showOverview($compositeContent)
    {
        foreach ($compositeContent as $ele) {
            print $ele['acf_fc_layout'];
            if (in_array($ele['acf_fc_layout'], ['lead_paragraph', 'paragraph_list'])) {
                print '  -  ';
                if ($ele['title']) {
                    print $ele['title'];
                }
                else if (isset($ele['description'])) {
                    print substr($ele['description'],0, 20) . '...';
                }
            }
            if ($ele['acf_fc_layout'] == 'text_item') {
                print '       -  ' . substr($ele['body'],0, 20) . '...';
            }
            print "\n";
        }
    }

    private function build($postId, $blocks, $dir, $materials, $time, $price, $difficulty, $language)
    {
        WP_CLI::line('');
        WP_CLI::line('');
        WP_CLI::line('************************');
        WP_CLI::line('************************ build start');
        WP_CLI::line('************************');
        WP_CLI::line('');

        WP_CLI::line('Article ID: ' . $postId);

        $post = get_post($postId);
        if (!$post) {
            WP_CLI::error('Article not found');
        }

        // Get composite content
        $compositeContent = get_field('composite_content', $postId);

        // Export composite content to a file
        self::exportOriginalContent($dir, $compositeContent, $postId);

        // Use start content if provided (great for testing / debugging)
        // This will overwrite the current compositeContent with the content in the import-file
        if ($this->startContent) {
            $compositeContent = $this->startContent;
        }

        // Output overview
        if ($this->outputOverview) {
            WP_CLI::line('Overview of ACF content already in WP');
            self::showOverview($compositeContent);
        }

        // Used when skipping upload of images
        $this->setExampleImageId($compositeContent);

        // Build the content
        $newContent = $this->buildBlocks($blocks, $dir, $postId);

        // Add materials
        $newContent[] = $this->buildMaterials($materials, $language, $time, $price, $difficulty);

        WP_CLI::line('');
        WP_CLI::line('************************');
        WP_CLI::line('************************ var_export(newContent) - start');
        WP_CLI::line('************************');
        var_export($newContent);
        WP_CLI::line('');
        WP_CLI::line('************************ var_export(newContent) - slut');
        WP_CLI::line('');

        // Output overview
        if ($this->outputOverview) {
            WP_CLI::line('Overview of new content');
            self::showOverview($newContent);
            WP_CLI::line('');
        }

        $finalContent = array_merge(
            self::addTopChapters($language),
            $compositeContent,
            self::addMiddleChapters($language),
            $newContent,
            self::addBottomChapters($language)
        );

        // Move the pdf-file to the last block
        $finalContent = self::movePdfFileToEnd($finalContent);

        // Move materials to after Materials chapter
        $finalContent = self::moveMaterials($finalContent);

        // Output overview
        if ($this->outputOverview) {
            WP_CLI::line('Overview of old and new content');
            self::showOverview($finalContent);
            exit;
        }

        /*
        var_dump($finalContent);
        print "\nslutx1234\n";
        exit;
        */

        return $finalContent;
    }

    private static function buildChapter($title)
    {
        return [
            'acf_fc_layout' => 'lead_paragraph',
            'title' => $title,
            'description' => '',
            'display_hint' => 'chapter',
        ];
    }

    private static function buildChapters($translations, $language)
    {
        $chapters = [];
        foreach ($translations[$language] as $title) {
            $chapters[] = self::buildChapter($title);
        }
        return $chapters;
    }

    private static function addTopChapters($language)
    {
        $translations = [
            'da' => ['Intro'],
            'sv' => ['Intro'],
            'no' => ['Intro'],
            'fi' => ['Johdanto'],
        ];
        return self::buildChapters($translations, $language);
    }

    private static function addMiddleChapters($language)
    {
        $translations = [
            'da' => ['Vejledning'],
            'sv' => ['Instruktion'],
            'no' => ['Veiledning'],
            'fi' => ['Ohjeet'],
        ];
        return self::buildChapters($translations, $language);
    }

    private static function addBottomChapters($language)
    {
        $translations = [
            'da' => ['Materialer', 'Tegning', 'Video', '3D-tegning', 'Tips & Tricks', 'Magasin-artikel'],
            'sv' => ['Material','Ritning','Video','3D-ritning','Tips & Tricks','Tidningsartikel'],
            'no' => ['Materialer','Tegning','Video','3D-tegning','Tips & Triks','Magasinartikkel'],
            'fi' => ['Materiaalit','Piirustus','Video','3D-piirustus','Vinkit & niksit','Lehtiversio'],
        ];
        return self::buildChapters($translations, $language);
    }

    /*
     * Find the file element and move it to the end
     */
    private static function movePdfFileToEnd($blocks)
    {
        $fileIndex = null;
        for ($i = 0; $i < sizeof($blocks); $i++) {
            if ($blocks[$i]['acf_fc_layout'] === 'file') {
                $fileIndex = $i;
                break;
            }
        }
        if ($fileIndex) {
            $fileElement = array_splice($blocks, $fileIndex, 1);
            $blocks = array_merge($blocks, $fileElement);
        }
        return $blocks;
    }

    private static function moveMaterials($blocks)
    {
        $fileIndex = null;
        for ($i = 0; $i < sizeof($blocks); $i++) {
            if ($blocks[$i]['acf_fc_layout'] === 'lead_paragraph' &&
                in_array($blocks[$i]['title'],['Materialer', 'Materiaalit', 'Materialer', 'Material'])) {
                $tmp = $blocks[$i];
                $blocks[$i] = $blocks[$i - 1];
                $blocks[$i - 1] = $tmp;
                return $blocks;
            }
        }

        return $blocks;
    }

    private function buildMaterials($materials, $language, $time, $price, $difficulty)
    {
        $timeLabel = [
            'da' => 'Tidsforbrug',
            'fi' => 'Vie aikaa',
            'nb' => 'Tidsforbruk',
            'sv' => 'Tidsförbrukning'
        ];

        $priceLabel = [
            'da' => 'Pris',
            'fi' => 'Hinta',
            'nb' => 'Pris',
            'sv' => 'Pris'
        ];

        $difficultyLabel = [
            'da' => 'Sværhedsgrad',
            'fi' => 'Vaikeusaste',
            'nb' => 'Vanskelighetsgrad',
            'sv' => 'Svårighetsgrad'
        ];

        $description = $materials .
            '<h3>' . $timeLabel[$language] . '</h3><p>' . $time . '</p>' .
            '<h3>' . $priceLabel[$language] . '</h3><p>' . $price . '</p>' .
            '<h3>' . $difficultyLabel[$language] . '</h3><p>' . $difficulty . '</p>';

        return [
            'acf_fc_layout' => 'paragraph_list',
            'title' => '',
            'description' => HtmlToMarkdown::parseHtml($description),
            'image' => false,
            'video_url' => '',
            'collapsible' => false,
            'display_hint' => 'box',
            'items' => false,
        ];
    }

    private function buildBlocks($blocks, $dir, $postId)
    {
        $content = [];
        foreach ($blocks as $block) {
            WP_CLI::line('Type: ' . $block['type']);
            if ($newContent = $this->buildWidget($block, $dir, $postId)) {
                // If type is how-to then add result directly at end of content
                // (to avoid array inside array)
                if (in_array($block['type'], ['how-to', 'boxout'])) {
                    $content = array_merge($content, $newContent);
                }
                else {
                    $content[] = $newContent;
                }
            }
        }
        return $content;
    }

    private static function logAuthors($postId, $authors)
    {
        file_put_contents('/tmp/authors.txt', $postId . ';' . $authors . PHP_EOL, FILE_APPEND);
    }

    private function buildWidget($block, $dir, $postId)
    {
        if ($block['type'] === 'author') {
            self::logAuthors($postId, $block['content']);
            /*
            WP_CLI::line('Widget: Text');
            WP_CLI::line($block['content']);
            return [
                'body'           => HtmlToMarkdown::parseHtml($block['content']),
                'locked_content' => true,
                'acf_fc_layout'  => 'text_item'
            ];
            */
            return null;
        }
        else if ($block['type'] === 'title') {
            /*
            print "<h1>(Set title)</h1>";
            print "(" . $block['content'] . ")<br>\n";
            */
            return null;
        }
        else if ($block['type'] === 'description') {
            /*
            print "<h1>(Set description)</h1>";
            print "(" . $block['content'] . ")<br>\n";
            */
            return null;
        }
        else if ($block['type'] === 'boxout') {
            //var_dump($block);exit;
            //print "* boxout *\n";
            return $this->buildBlocks($block['content'], $dir, $postId);
        }
        else if ($block['type'] === 'h2') {
            WP_CLI::line('Widget: Text');
            WP_CLI::line('<h2>' . $block['content'] . '</h2> x');

            /*
            if (preg_match("/riller/", $block['content'], $res)) {
                print $block['content'] . " a ";
                exit;
            }
            else {
                print $block['content'] . " b ";
            }
            */

            return [
                'body'           => HtmlToMarkdown::parseHtml('<h2>' . $block['content'] . '</h2>'),
                'locked_content' => true,
                'acf_fc_layout'  => 'text_item'
            ];
        }
        else if ($block['type'] === 'h3') {
            WP_CLI::line('Widget: Text');
            WP_CLI::line('<h3>' . $block['content'] . '</h3>');
            return [
                'body'           => HtmlToMarkdown::parseHtml('<h3>' . $block['content'] . '</h3>'),
                'locked_content' => true,
                'acf_fc_layout'  => 'text_item'
            ];
        }
        else if ($block['type'] === 'how-to') {
            WP_CLI::line('************************ how-to');
            return $this->buildBlocks($block['content'], $dir, $postId);
        }
        else if ($block['type'] === 'how-to-section') {
            return $this->buildHowToSection($block['content'], $dir, $postId);
        }
        else if ($block['type'] === 'image') {
            WP_CLI::line('Widget: image');
            WP_CLI::line('Dir: ' . $dir);
            WP_CLI::line('src: ' . $block['src']);

            $figcaption = '';
            if (isset($block['figcaption'])) {
                $figcaption = HtmlToMarkdown::parseHtml($block['figcaption']);
                WP_CLI::line("figcaption: " . $figcaption);
            }

            $fileAttachmentId = $this->uploadFile($postId, $dir, $block['src'], $figcaption);
            WP_CLI::line('## Attachment ID Image ##: ' . $fileAttachmentId);
            return [
                'lead_image' => false,
                'file' => $fileAttachmentId,
                'locked_content' => true,
                'acf_fc_layout' => 'image'
            ];
        }
        else if ($block['type'] === 'meta') {
            // Do not use meta
        }
        else if ($block['type'] === 'text') {
            WP_CLI::line('Widget: Text');
            WP_CLI::line($block['content']);
            return [
                'body'           => self::stripSlashes(HtmlToMarkdown::parseHtml($block['content'])),
                'locked_content' => true,
                'acf_fc_layout'  => 'text_item'
            ];
        }
        else if ($block['type'] === 'offer') {
            WP_CLI::line('Widget: Text');
            WP_CLI::line($block['content']);
            return [
                'body'           => HtmlToMarkdown::parseHtml($block['content']),
                'locked_content' => true,
                'acf_fc_layout'  => 'text_item'
            ];
        }
        else if ($block['type'] === 'list') {
            WP_CLI::line('Widget: Text');
            $content = '<ul>';
            foreach ($block['content'] as $ele) {
                $content .= '<li>' . $ele . '<br>';
            }
            $content .= '</ul>';

            return [
                'body'           => HtmlToMarkdown::parseHtml($content),
                'locked_content' => true,
                'acf_fc_layout'  => 'text_item'
            ];
        }
        else if (!in_array($block['type'], ['metabox'])) {  // Allow metabox to be skipped, otherwise error
            WP_CLI::error("(buildWidget) ERROR, type not handled: " . $block['type']);
            exit;
        }
        print "<br>\n";
    }

    private static function stripSlashes($data)
    {
        return preg_replace("/(\d+)\\\\+\./", "$1.", $data);
    }

    private function uploadFile($postId, $dir, $src, $figcaption)
    {
        if ($this->skipImageUpload) {
            return $this->exampleImageId;
        }

        WP_CLI::line('*** UPLOAD IMAGE ***');
        $file = $dir . '/' . $src;
        $fileName = $src;
        return WpAttachment::upload_file($postId, $file, $fileName, $figcaption);
    }

    private function buildHowTo($blocks)
    {
        foreach ($blocks as $block) {
            $this->buildWidget($block);
        }
    }

    private function buildBoxout($blocks)
    {
        foreach ($blocks as $block) {
            $this->buildWidget($block);
        }
    }

    private function buildHowToSection($blocks, $dir, $postId)
    {
        $title = '';
        $description = '';
        $image = false;
        foreach ($blocks as $block) {
            if ($block['type'] === 'h2') {
                $title = $block['content'];
            }
            else if ($block['type'] === 'text') {
                if ($description) {
                    $description .= '<br><br>';
                }
                $description .= $block['content'];
            }
            else if ($block['type'] === 'image') {
                $image = $block;
            }
        }

        print "<h1>Widget: Paragraph List</h1>";
        print "Title:<br>" . $title . "<br><br>";
        print "Description:<br>" . $description . "<br>";


        $fileAttachmentId = false;
        if ($image) {
            $figcaption = false;
            if (isset($image['figcaption'])) {
                $figcaption = HtmlToMarkdown::parseHtml($image['figcaption']);
                WP_CLI::line("figcaption: " . $figcaption);
            }

            $fileAttachmentId = $this->uploadFile($postId, $dir, $image['src'], $figcaption);
            WP_CLI::line('## Attachment ID Paragraph List ##: ' . $fileAttachmentId);
        }

        $items = [];
        foreach ($blocks as $block) {
            if ($block['type'] === 'how-to-step') {
                $items[] = $this->buildHowToStep($block['content'], $dir, $postId);
            }
        }

        // ACF needs items to be false if empty
        if (sizeof($items) === 0) {
            $items = false;
        }

        return [
            'acf_fc_layout' => 'paragraph_list',
            'title' => self::stripSlashes(HtmlToMarkdown::parseHtml($title)),
            'description' => HtmlToMarkdown::parseHtml($description),
            'image' => $fileAttachmentId,
            'video_url' => '',
            'collapsible' => false,
            'display_hint' => 'ordered',
            'items' => $items,
        ];
    }

    private function buildHowToStep($blocks, $dir, $postId)
    {
        WP_CLI::line('<h1>Paragraph List Item</h1>');

        $stepNumber = false;
        $image = false;
        $direction = false;
        foreach ($blocks as $block) {
            if ($block['type'] === 'meta') {
                $stepNumber = $block['content'];
            }
            else if ($block['type'] === 'link') {
            }
            else if ($block['type'] === 'image') {
                $image = $block;
            }
            else if ($block['type'] === 'direction') {
                $direction = $block['content'];
            }
            else {
                WP_CLI::error("<h1>ERROR, not matched</h1>");
                exit;
            }
        }

        // Build array to be returned
        $data = [];
        $data['image'] = false;

        // Set title to step number
        if ($stepNumber) {
            $data['title'] = $stepNumber;
        }

        // Set description to direction (text under image)
        if ($direction) {
            $data['description'] = HtmlToMarkdown::parseHtml($direction);
            WP_CLI::line('direction: ' . $direction);
        }

        if ($image) {
            $figcaption = false;
            if (isset($image['figcaption'])) {
                $figcaption = HtmlToMarkdown::parseHtml($image['figcaption']);
                WP_CLI::line("figcaption: " . $figcaption);
            }

            $data['image'] = $this->uploadFile($postId, $dir, $image['src'], $figcaption);
            WP_CLI::line('## Attachment ID Paragraph List Item ##: ' . $data['image']);   // attachment id
        }

        return $data;
    }

    private function log($line)
    {
        if ($this->log) {
            $date = date('Y-m-d H:i:s');
            file_put_contents($this->log, $date . ' - ' . $line . PHP_EOL, FILE_APPEND);
        }
    }

    private function setAuthor($authorName, $postId)
    {
        if ($authorName) {
            $author = self::getAuthor($authorName);

            if ($author->ID) {
                WP_CLI::line(sprintf('Updating post %s, set author_id %s', $postId, $author->ID));
                wp_update_post([
                    'ID' => $postId,
                    'post_author' => $author->ID,
                ]);
            }
        }
    }

    private function setDefaultAuthor($language, $postId)
    {
        if (!$language) {
            WP_CLI::error('Language not set');
        }

        $author = WpAuthor::getDefaultAuthor($language);
        if (!$author) {
            WP_CLI::error('Error getting default author for language: ' . $language);
        }

        WP_CLI::line(sprintf('Updating post %s, set author_id %s', $postId, $author->ID));
        wp_update_post([
            'ID' => $postId,
            'post_author' => $author->ID,
        ]);
    }

    private static function getAuthor($authorName): ?WP_User
    {
        if (!empty($authorName)) {
            $author = WpAuthor::findOrCreate($authorName);
            var_dump($author);
            if ($author instanceof WP_User) {
                return $author;
            }
        }
        return null;
    }

    private function setExampleImageId($content)
    {
        foreach($content as $ele) {
            if ($ele['acf_fc_layout'] === 'image') {
                $this->exampleImageId = $ele['file']['ID'];
                return;
            }
        }
        $this->exampleImageId = false;
    }
}