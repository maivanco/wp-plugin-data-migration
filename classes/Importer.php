<?php

namespace ITCDataMigration;

class Importer{

    const JSON_IMPORT_DIR = ITC_DM_DIR .'assets/json/';
    const JSO0_IMPORT_URL = ITC_DM_URL .'assets/json/';

    private $sourceHomeUrl;

    public function __construct()
    {

    }
    public function importData($file) {

        $res = $this->validateFile($file);

        if($res['status'] == 'error') {
            return $res;
        }

        // Read the file contents into a string.
        $fileContents = file_get_contents($file['tmp_name']);
        $fileContents = json_decode($fileContents, true);

        $res = $this->insertData($fileContents);

        return $res;
    }

    private function insertData($fileContents){

        $this->sourceHomeUrl = $fileContents['source_home_url'];

        foreach($fileContents['posts'] as $row){
            $latestPostID = $this->insertPostData($row['post_data']);
            if ( $latestPostID > 0) {
                $this->insertRelatedPostMeta($latestPostID, $row['related_post_meta']);
                $this->insertRelatedTerms($latestPostID, $row['related_terms']);
            }
        }
        return [
            'status' => 'success',
            'msg' => __('The data has been imported','itc-dm')
        ];
    }

    private function insertPostData(array $postData): int
    {
        unset($postData['ID']);
        unset($postData['guid']);

        $postData['post_content'] = $this->filterMediaUrlInPostContent($postData['post_content']);

        return (int) wp_insert_post($postData);
    }
    private function filterMediaUrlInPostContent(string $postContent = ''){
        //Handle image urls
        $imageUrls = preg_match_all('/<img.*src=[\'"]([^\'"\s]+)[\'"].*>/', $postContent, $matches);
        $imageUrls = $matches[1];
        if( !empty($imageUrls) ) {
            $imageUrls = array_unique($imageUrls);
            foreach($imageUrls as $url) {

                if(strpos($url, $this->sourceHomeUrl) === false ){
                    continue;
                }

                $src = $this->convertImageUrlToSrc($url);
                if (!empty($src)) {
                    $postContent = str_replace($url, $src, $postContent);
                }

            }
        }

        //Handle file urls
        $fileUrls = preg_match_all('/<a.*href=[\'"]([^\'"\s]+)[\'"].*>/', $postContent, $matches);
        $fileUrls = $matches[1];

        if( !empty($fileUrls) ) {
            $fileUrls = array_unique($fileUrls);
            foreach($fileUrls as $url) {

                if(strpos($url, $this->sourceHomeUrl) === false ){
                    continue;
                }

                $src = $this->convertFileUrlToSrc($url);
                if (!empty($src)) {
                    $postContent = str_replace($url, $src, $postContent);
                }

            }
        }


        return $postContent;

    }
    private function insertRelatedPostMeta(int $latestPostID,array $postMeta){
        foreach($postMeta as $row){
            $row['meta_value'] = $this->formatValueByACFType($row['acf_type'], $row['meta_value']);

            if ($row['meta_key'] == '_thumbnail_id') {
                $row['meta_value'] = $this->convertImageUrlToAttachmentID($row['meta_value']);
            }
            update_post_meta($latestPostID, $row['meta_key'], $row['meta_value']);
        }
    }
    private function insertRelatedTerms(int $postID, array $termData){

        if (empty($termData)) {
            return false;
        }

        foreach($termData as $row) {
            $termItem = $row['term_item'];

            $newTerm = $this->maybeCreateNewTerm(
                $termItem['name'],
                $termItem['taxonomy']
            );

            if (empty($newTerm)) {
                continue;
            }
            $termID = (int) $newTerm['term_id'];
            //assign term to the post
            wp_set_object_terms($postID, $termID, $termItem['taxonomy']);

            //Add or update term meta
            $this->addOrUpdateTermMeta($termID, $row['term_meta']);
        }
    }

    private function addOrUpdateTermMeta($termID, $termMetaData){
        if ( empty($termMetaData)) {
            return false;
        }

        foreach($termMetaData as $row) {
            $row['meta_value'] = $this->formatValueByACFType(
                $row['acf_type'],
                $row['meta_value']
            );
            update_term_meta(
                $termID,
                $row['meta_key'],
                $row['meta_value']
            );
        }
    }

    private function maybeCreateNewTerm($termName, $taxName){
        $termName = trim($termName);
        if(empty($termName)){
            return [];
        }

        $term = term_exists($termName, $taxName);
        if ($term) {
            return $term;
        } else{
            $term = wp_insert_term($termName, $taxName);
            if (!is_wp_error($term)) {
                return  $term;
            }
        }
        return [];
    }

    private function formatValueByACFType($acfType, $metaValue)
    {
        switch($acfType){
            case 'image':
                $metaValue = $this->convertImageUrlToAttachmentID($metaValue);
                break;
            case 'file':
                $metaValue = $this->convertFileUrlToAttacthmentID($metaValue);

                break;
            case 'gallery':
                $imgUrls = $metaValue;
                $attachmentIDs = [];
                if( !empty($imgUrls) ) {
                    $imgUrls = json_decode($imgUrls, true);
                    foreach($imgUrls as $imgUrl) {
                        $attachmentIDs[] = $this->convertImageUrlToAttachmentID($imgUrl);
                    }
                    //Filter empty values
                    $attachmentIDs = array_filter($attachmentIDs);
                    $metaValue = $attachmentIDs;
                }
                break;
            default:

        }
        return $metaValue;
    }

    private function convertImageUrlToAttachmentID($imgUrl){
        $attachmentID = !empty($imgUrl) ? media_sideload_image($imgUrl, 0, null, 'id') : $imgUrl;
        return !is_wp_error($attachmentID) && (int) $attachmentID > 0 ? $attachmentID : '';
    }
    private function convertFileUrlToSrc($imgUrl){
        $attactmentID = $this->convertFileUrlToAttacthmentID($imgUrl);
        $src = '';
        if (!is_wp_error($attactmentID)) {
            $src = wp_get_attachment_url($attactmentID);
        }
        return $src;
    }

    private function convertFileUrlToAttacthmentID($fileUrl){

        $file_extension = pathinfo( $fileUrl, PATHINFO_EXTENSION );

        // Determine the mime type of the file.
        $mime_type = wp_get_image_mime( $file_extension );

        // Download the file from the URL.
        $file_name = download_url( $fileUrl );

        // Create the attachment.
        $media_file = wp_insert_attachment( [
            'file' => $file_name,
            'title' => basename( $fileUrl ),
            'mime_type' => $mime_type,
        ] );

        // Return the attachment ID.
        return $media_file;

    }

    private function convertImageUrlToSrc($imgUrl){
        $src = !empty($imgUrl) ? media_sideload_image($imgUrl, 0, null, 'src') : $imgUrl;
        return !is_wp_error($src) ? $src : '';
    }


    private function validateFile($file){
        $resStatus = $this->getStatusResponse();

        $defaultResponse = $resStatus['upload_success'];

        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            return $resStatus['upload_err'];
        }

        // Get the file name and extension.
        $file_name = $file['name'];
        $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);

        // Check if the file extension is allowed.
        $allowed_extensions = ['json'];
        if (!in_array($file_extension, $allowed_extensions)) {
            $defaultResponse = $resStatus['file_ext_err'];
        }
        return $defaultResponse;
    }

    private function getStatusResponse(){
        return [
            'upload_err' => [
                'status' => 'error',
                'msg' => __('The file is empty or error while uploading', 'itc-dm')
            ],
            'upload_success' => [
                'status' => 'success',
                'msg' => __('The file has been imported successfully.', 'itc-dm')
            ],
            'file_ext_err' => [
                'status' => 'error',
                'msg' => __('File extension is not allowed', 'itc-dm')
            ],
            'file_move_failed' => [
                'status' => 'error',
                'msg' => __('File move failed!', 'itc-dm')
            ]
        ];
    }
}
