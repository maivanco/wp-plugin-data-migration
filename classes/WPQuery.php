<?php

namespace ITCDataMigration;

class WPQuery{
    public function getPostsBy($postParams = []){

        $defaultParams = $this->getDefaultPostParams();

        $postParams = $this->validatePostParams($postParams);

        $postParams = $this->formatPostParams($postParams, $defaultParams);


        $queryPosts = new \WP_Query($postParams);
        $select2FormattedPosts = [
            'results' => [],
            'pagination' => [
                'more' => ($postParams['page'] * $postParams['posts_per_page']) < $queryPosts->found_posts
            ]
        ];

        if ($postParams['page'] == 1) {
            $select2FormattedPosts['results'][] = [
                'id' => 'all',
                'text' => __('All', 'itc-dm')
            ];
        }

        if( $queryPosts->have_posts()  ) {
            while( $queryPosts->have_posts() ):
                $queryPosts->the_post();

                $select2FormattedPosts['results'][] = [
                    'id' => get_the_ID(),
                    'text' => get_the_title()
                ];

            endwhile;
            wp_reset_postdata();
        }

        return $select2FormattedPosts;
    }

    private function formatPostParams($postParams, $defaultPostParams){

        $formattedParams = $defaultPostParams;

        $formattedParams['post_type'] = isset($postParams['post_type']) ? $postParams['post_type'] : 'post';
        $formattedParams['page'] = isset($postParams['page']) ? $postParams['page'] : 1;
        $formattedParams['offset'] = $formattedParams['page'] > 1 ?
                                    ($formattedParams['page'] - 1) * $defaultPostParams['posts_per_page'] :
                                    0;
        $formattedParams['s'] = isset($postParams['search']) ? $postParams['search'] : null;

        return $formattedParams;
    }

    private function validatePostParams($postParams){

        if (isset($postParams['post_type'])) {
            $postParams['post_type'] = sanitize_text_field($postParams['post_type']);
        }

        if( isset( $postParams['page'])) {
            $postParams['page'] = (int) $postParams['page'];
        }

        if( isset( $postParams['search'])) {
            $postParams['search'] = sanitize_text_field($postParams['search']);
        }

        return $postParams;
    }

    private function getDefaultPostParams(){
        return [
            'post_type' => 'post',
            'posts_per_page' => get_option('posts_per_page'),
            'page' => 1,
            'offset' => 0,
            's' => null
        ];
    }

}