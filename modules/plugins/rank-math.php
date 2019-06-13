<?php
/**
 * Manages the compatibility with the Rank Math plugin
 *
 * @since 2.5.5
 */
class PLL_RankMath {
  /**
   * Add specific filters and actions
   *
   * @since 2.5.5
   */
  public function init() {
    if ( ! defined( 'RANK_MATH_VERSION' ) ) {
      return;
    }

    if ( PLL() instanceof PLL_Frontend ) {
      // Filters sitemap queries to remove inactive language or to get
      // one sitemap per language when using multiple domains or subdomains
      // because Rank Math does not accept several domains or subdomains in one sitemap
      add_filter( 'rank_math/sitemap/typecount_join', array( $this, 'rank_math_posts_join' ), 10, 2 );
      add_filter( 'rank_math/sitemap/typecount_where', array( $this, 'rank_math_posts_where' ), 10, 2 );

      if ( PLL()->options['force_lang'] > 1 ) {
        add_filter( 'rank_math/sitemap/enable_caching', '__return_false' ); // Disable cache! otherwise Rank Math keeps only one domain
        add_filter( 'home_url', array( $this, 'rank_math_home_url' ), 10, 2 ); // Fix home_url
      } else {
        add_filter( 'rank_math/sitemap/enable_caching', '__return_false' );
        // Get all terms in all languages when the language is set from the content or directory name
        add_filter( 'get_terms_args', array( $this, 'rank_math_remove_terms_filter' ) );
      }

      add_filter( 'pll_home_url_white_list', array( $this, 'rank_math_home_url_white_list' ) );
    }
  }

  /**
   * Fixes the home url as well as the stylesheet url
   * Only when using multiple domains or subdomains
   *
   * @since 2.5.5
   *
   * @param string $url
   * @param string $path
   * @return $url
   */
  public function rank_math_home_url( $url, $path ) {
    $uri = empty( $path ) ? ltrim( $_SERVER['REQUEST_URI'], '/' ) : $path;

    if ( 'sitemap_index.xml' === $uri || preg_match( '#([^/]+?)-sitemap([0-9]+)?\.xml|([a-z]+)?-?sitemap\.xsl#', $uri ) ) {
      $url = PLL()->links_model->switch_language_in_link( $url, PLL()->curlang );
    }

    return $url;
  }

  /**
   * Get active languages for the sitemaps
   *
   * @since 2.5.5
   *
   * @return array list of active language slugs, empty if all languages are active
   */
  protected function rank_math_get_active_languages() {
    $languages = PLL()->model->get_languages_list();
    if ( wp_list_filter( $languages, array( 'active' => false ) ) ) {
      return wp_list_pluck( wp_list_filter( $languages, array( 'active' => false ), 'NOT' ), 'slug' );
    }
    return array();
  }

  /**
   * Modifies the sql request for posts sitemaps
   * Only when using multiple domains or subdomains or if some languages are not active
   *
   * @since 2.5.5
   *
   * @param string $sql       JOIN clause
   * @param string $post_type
   * @return string
   */
  public function rank_math_posts_join( $sql, $post_type ) {
    return pll_is_translated_post_type( $post_type ) && ( PLL()->options['force_lang'] > 1 || $this->rank_math_get_active_languages() ) ? $sql . PLL()->model->post->join_clause() : $sql;
  }

  /**
   * Modifies the sql request for posts sitemaps
   * Only when using multiple domains or subdomains or if some languages are not active
   *
   * @since 2.5.5
   *
   * @param string $sql       WHERE clause
   * @param string $post_type
   * @return string
   */
  public function rank_math_posts_where( $sql, $post_type ) {
    if ( pll_is_translated_post_type( $post_type ) ) {
      if ( PLL()->options['force_lang'] > 1 ) {
        return $sql . PLL()->model->post->where_clause( PLL()->curlang );
      }

      if ( $languages = $this->rank_math_get_active_languages() ) {
        return $sql . PLL()->model->post->where_clause( $languages );
      }
    }
    return $sql;
  }

  /**
   * Removes the language filter (and remove inactive languages) for the taxonomy sitemaps
   * Only when the language is set from the content or directory name
   *
   * @since 2.5.5
   *
   * @param array $args get_terms arguments
   * @return array modified list of arguments
   */
  public function rank_math_remove_terms_filter( $args ) {
    if ( isset( $GLOBALS['wp_query']->query['sitemap'] ) ) {
      $args['lang'] = implode( ',', $this->rank_math_get_active_languages() );
    }
    return $args;
  }
}
