<?php
    /**
     * Interface: Feather
     * Describes the functions required by Feather implementations.
     */
    interface Feather {
        /**
         * Function: submit
         * Handles post submitting.
         *
         * Returns:
         *     The <Post> object created.
         */
        public function submit(
        ): Post;

        /**
         * Function: update
         * Handles updating a post.
         */
        public function update(
            $post
        ): Post|false;

        /**
         * Function: title
         * Returns the appropriate source to be treated as a "title" of a post.
         * If there is no immediate solution, you may use <Post::title_from_excerpt>.
         */
        public function title(
            $post
        ): string;

        /**
         * Function: excerpt
         * Returns the appropriate source, unmodified, to be used as an excerpt of a post.
         */
        public function excerpt(
            $post
        ): string;

        /**
         * Function: feed_content
         * Returns the appropriate content for a feed.
         */
        public function feed_content(
            $post
        ): string;
    }
