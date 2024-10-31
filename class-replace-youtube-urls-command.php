<?php
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
    return;
}

class YouTube_URL_Replace_Command extends WP_CLI_Command {

    private $csv_data = [];

    /**
     * Search and replace YouTube URLs in the wp_posts table of all sites in a multisite installation, ignoring revisions.
     *
     * ## OPTIONS
     *
     * [--dry-run]
     * : If set, the command will only display the changes without making them.
     *
     * [--export]
     * : If set, the results will be exported to a CSV file.
     *
     * [--clear-oembed-cache]
     * : If set, the command will delete all post meta keys starting with '_oembed_' for posts containing youtu.be or youtube.com URLs.
     *
     * ## EXAMPLES
     *
     *     wp youtube-replace --dry-run --export --clear-oembed-cache
     *
     * @param array $args
     * @param array $assoc_args
     */
    public function __invoke( $args, $assoc_args ) {
        $dry_run           = isset( $assoc_args['dry-run'] );
        $export            = isset( $assoc_args['export'] );
        $clear_oembed_cache = isset( $assoc_args['clear-oembed-cache'] );

        if ( is_multisite() ) {
            $sites = get_sites();
            foreach ( $sites as $site ) {
                switch_to_blog( $site->blog_id );
                $this->process_wp_posts( $dry_run, $site->blog_id, $clear_oembed_cache );
                restore_current_blog();
            }
        } else {
            $this->process_wp_posts( $dry_run, get_current_blog_id(), $clear_oembed_cache );
        }

        // Export the results to a CSV file if the option is set
        if ( $export ) {
            $this->export_to_csv();
        }
    }

    /**
     * Process the wp_posts table of the current site, ignoring revisions.
     *
     * @param bool $dry_run
     * @param int $blog_id
     * @param bool $clear_oembed_cache
     */
    private function process_wp_posts( $dry_run, $blog_id, $clear_oembed_cache ) {
        global $wpdb;

        // Fetch all posts from wp_posts that are not revisions
        $posts = $wpdb->get_results( "SELECT ID, post_content FROM {$wpdb->posts} WHERE (post_content LIKE '%youtu.be%' OR post_content LIKE '%youtube.com%') AND post_type != 'revision'", ARRAY_A );

        foreach ( $posts as $post ) {
            $post_id = $post['ID'];
            $post_content = $post['post_content'];

            // Extract all URLs (ignoring anything after quotation marks)
            preg_match_all( '/https?:\/\/[^\s"]+/', $post_content, $matches );

            if ( ! empty( $matches[0] ) ) {
                foreach ( $matches[0] as $url ) {
                    // Process youtu.be URLs
                    if ( strpos( $url, 'youtu.be' ) !== false ) {
                        $this->handle_replacement( $post_id, $post_content, $url, 'youtu.be', 'youtube.com/embed', $dry_run, $blog_id );
                    }

                    // Process '\u0026' in youtube.com URLs
                    if ( strpos( $url, 'youtube.com' ) !== false && strpos( $url, '\u0026' ) !== false ) {
                      //  $this->handle_replacement( $post_id, $post_content, $url, '\u0026', '&amp;', $dry_run, $blog_id );
                    }
                }
            }

            // Clear oEmbed cache if the option is set
            if ( $clear_oembed_cache ) {
                $this->clear_oembed_cache( $post_id );
            }
        }
    }

    /**
     * Handle the replacement logic and logging.
     *
     * @param int    $post_id
     * @param string $post_content
     * @param string $url
     * @param string $search
     * @param string $replace
     * @param bool   $dry_run
     * @param int    $blog_id
     */
    private function handle_replacement( $post_id, $post_content, $url, $search, $replace, $dry_run, $blog_id ) {
        global $wpdb;

        // Replace the URL in the content
        $new_url = str_replace( $search, $replace, $url );

        if ( $url !== $new_url ) {
            // Build the correct permalink using the blog ID
            $site_url = get_home_url( $blog_id );
            $post_slug = get_post_field( 'post_name', $post_id );
            $post_permalink = trailingslashit( $site_url ) . $post_slug;

            // Log the original and replaced URLs with the site ID
            WP_CLI::log( sprintf( "Site ID: %d | Post ID: %d | Permalink: %s", $blog_id, $post_id, $post_permalink ) );
            WP_CLI::log( sprintf( "Original: %s", $url ) );
            WP_CLI::log( sprintf( "Replaced: %s", $new_url ) );

            // Prepare data for CSV export with site ID
            $this->csv_data[] = [
                'site_id'       => $blog_id,
                'post_id'       => $post_id,
                'post_permalink'=> $post_permalink,
                'original_url'  => $url,
                'replaced_url'  => $new_url,
            ];

            if ( ! $dry_run ) {
                // Update the post content
                $updated_content = str_replace( $url, $new_url, $post_content );
                $wpdb->update(
                    $wpdb->posts,
                    [ 'post_content' => $updated_content ],
                    [ 'ID' => $post_id ]
                );

                // Clean post cache
                clean_post_cache( $post_id );
            }
        }
    }

    /**
     * Clear oEmbed cache by deleting meta keys starting with '_oembed_' for the given post ID.
     *
     * @param int $post_id
     */
    private function clear_oembed_cache( $post_id ) {
        global $wpdb;

        // Delete post meta keys starting with '_oembed_'
        $wpdb->query( $wpdb->prepare(
            "DELETE FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key LIKE '_oembed_%'",
            $post_id
        ) );

        // Log the cache clearing action
        WP_CLI::log( sprintf( "Cleared oEmbed cache for Post ID: %d", $post_id ) );
    }

    /**
     * Export the log data to a CSV file.
     */
    private function export_to_csv() {
        $filename = 'youtube_url_replacements_' . date( 'Y-m-d_H-i-s' ) . '.csv';
        $filepath = WP_CONTENT_DIR . '/uploads/' . $filename;

        $file = fopen( $filepath, 'w' );

        // Add CSV headers including Site ID
        fputcsv( $file, [ 'Site ID', 'Post ID', 'Permalink', 'Original URL', 'Replaced URL' ] );

        // Add data rows
        foreach ( $this->csv_data as $row ) {
            fputcsv( $file, $row );
        }

        fclose( $file );

        WP_CLI::success( sprintf( 'CSV file has been exported to: %s', $filepath ) );
    }
}

WP_CLI::add_command( 'youtube-replace', 'YouTube_URL_Replace_Command' );
