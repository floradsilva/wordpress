<div class="ld-item-search ld-profile-search-string">
    <div class="ld-item-search-wrapper">
        <span class="ld-text"><?php echo sprintf(
            // translators: placeholder: search term.
            _x( 'You searched for "%s"', 'placeholder: search term', 'learndash' ), esc_html( $search_query ) ); ?></span>
        <a class="ld-reset-link" href="<?php the_permalink(); ?>"><?php esc_html_e( 'Reset', 'learndash' ); ?></a>
    </div>
</div> <!--/.ld-profile-search-string-->
