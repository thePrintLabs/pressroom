<?php
/**
 *
 * Create the PropertyList pressroom.plist by using the CFPropertyList API.
 * @package plist
 * @subpackage plist.examples
 */

namespace CFPropertyList;

 // @TODO remove error reporting
error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

require_once( PR_LIBS_PATH . DS . 'CFPropertyList/CFPropertyList.php');


class pressroom_Plist extends \PR_Server_API {

  public function __construct() {

    add_action( 'press_flush_rules', array( $this, 'add_endpoint' ), 10 );
    add_action( 'init', array( $this, 'add_endpoint' ), 10 );
    add_action( 'parse_request', array( $this, 'parse_request' ), 10 );
  }

  /**
   * Add API Endpoint
   * Must extend the parent class
   *
   *	@void
   */
  public function add_endpoint() {

    parent::add_endpoint();
    add_rewrite_rule( 'pressroom-api/client-settings/([^&]+)/?$',
                      'index.php?__pressroom-api=client_settings&editorial_project=$matches[1]',
                      'top' );
  }

  /**
   * Parse HTTP request
   * Must extend the parent class
   *
   *	@return die if API request
   */
  public function parse_request() {

    global $wp;
    $request = parent::parse_request();

    if ( $request && $request == 'client_settings' ) {
      $this->_action_get_settings();
    }
  }

  protected function _action_get_settings() {

    global $wp;
    $eproject_slug = $wp->query_vars['editorial_project'];
    if ( !$eproject_slug ) {
      $this->send_response( 400, 'Bad request. Please specify an editorial project.' );
    }
    $eproject = get_term_by( 'slug', $eproject_slug, PR_EDITORIAL_PROJECT );

    $plist = new CFPropertyList();

    $plist->add( $dict = new CFDictionary() );

    $dict->add( 'isNewsstand', new CFBoolean( true ) );
    $dict->add( 'newsstandLatestIssueCover', new CFBoolean( true ) );
    $dict->add( 'newsstandManifestUrl', new CFString( site_url() . DS . "pressroom-api/shelf/{$eproject_slug}" ) );
    $dict->add( 'purchaseConfirmationUrl', new CFString( site_url() . DS . "pressroom-api/purchaseConfirmationUrl/:app_id/:user_id/{$eproject_slug}" ) );
    $dict->add( 'purchasesUrl', new CFString( site_url() . DS . "pressroom-api/itunes_purchases_list/:app_id/:user_id/{$eproject_slug}" ) );
    $dict->add( 'postApnsTokenUrl', new CFString( site_url() . DS . "pressroom-api/apns_token/:app_id/:user_id/{$eproject_slug}" ) );
    $dict->add( 'authenticationUrl', new CFString( site_url() . DS . "pressroom-api/sullivan_login/:app_id/:user_id/{$eproject_slug}" ) );
    $dict->add( 'freeSubscriptionProductId', new CFString( "" ));
    $dict->add( 'autoRenewableSubscriptionProductIds', $productIds = new CFArray() );

    $methods = $this->get_subscription_method( $eproject->term_id );
    foreach( $methods as $method ) {
      $productIds->add( new CFString( $method ) );
    }
    //$dict->add( 'requestTimeout', new CFNumber( 15 ) );

    /* Pad */
    $dict->add( 'Pad', $pad = new CFDictionary() );

    /* Pad Issue Shelf Shelf */
    $hpub_pad = get_option( 'hpub_pad' );
    $pad->add( 'issuesShelfOptions', $padShelf = new CFDictionary() );
    $padShelf->add( 'layoutType', new CFString( 'grid' ) );
    $padShelf->add( 'gridColumns', new CFNumber( 2 ) );
    $padShelf->add( 'scrollDirection', new CFString( 'vertical' ) );
    $padShelf->add( 'pagingEnabled', new CFBoolean( true ) );
    $padShelf->add( 'backgroundFillStyle', new CFString( isset( $hpub_pad['backgroundFillStyle'] ) ? $hpub_pad['backgroundFillStyle'] : '' ) );
    $padShelf->add( 'backgroundFillGradientStart', new CFString( isset( $hpub_pad['backgroundFillGradientStart'] ) ? $hpub_pad['backgroundFillGradientStart'] : '' ) );
    $padShelf->add( 'backgroundFillGradientStop', new CFString( isset( $hpub_pad['backgroundFillGradientStop'] ) ? $hpub_pad['backgroundFillGradientStop'] : '' ) );
    $padShelf->add( 'backgroundFillColor', new CFString( isset( $hpub_pad['backgroundFillColor'] ) ? $hpub_pad['backgroundFillColor'] : '') );
    $padShelf->add( 'backgroundFitStyle', new CFBoolean( isset( $hpub_pad['backgroundFitStyle'] ) ? $hpub_pad['backgroundFitStyle'] : false ) );
    $padShelf->add( 'headerHidden', new CFBoolean( isset( $hpub_pad['headerHidden'] ) ? $hpub_pad['headerHidden'] : false  ) );
    $padShelf->add( 'headerSticky', new CFBoolean( isset( $hpub_pad['headerSticky'] ) ? $hpub_pad['headerSticky'] : false ) );
    $padShelf->add( 'headerStretch', new CFBoolean( isset( $hpub_pad['headerStretch'] ) ? $hpub_pad['headerStretch'] : false ) );
    $padShelf->add( 'headerBackgroundColor', new CFString( isset( $hpub_pad['headerBackgroundColor'] ) ? $hpub_pad['headerBackgroundColor'] : '' ) );
    $padShelf->add( 'headerImageFill', new CFBoolean( isset( $hpub_pad['headerImageFill'] ) ? $hpub_pad['headerImageFill'] : false ) );
    $padShelf->add( 'headerHeightLandscape', new CFNumber( 118 ) );
    $padShelf->add( 'headerHeightPortrait', new CFNumber( 118 ) );

    /* Pad Issue Option */
    $pad->add( 'issuesOptions', $padIssue = new CFDictionary() );
    /* Portrait */
    $padIssue->add( 'portrait', $padIssuePortrait = new CFDictionary() );
    $padIssuePortrait->add( 'cellHeight', new CFNumber( 270 ) );

    $padIssuePortrait->add( 'cellPadding', $portraitCellpadding = new CFDictionary() );
    $portraitCellpadding->add( 'top', new CFNumber( 70 ) );
    $portraitCellpadding->add( 'right', new CFNumber( 25 ) );
    $portraitCellpadding->add( 'bottom', new CFNumber( 0 ) );
    $portraitCellpadding->add( 'left', new CFNumber( 25 ) );

    $padIssuePortrait->add( 'titleLabel', $portraitTitleLabel = new CFDictionary() );
    $portraitTitleLabel->add( 'align', new CFString( isset( $hpub_pad['portrait']['titleLabelAlign'] ) ? $hpub_pad['portrait']['titleLabelAlign'] : 'left'  );
    $portraitTitleLabel->add( 'font', new CFString( 'Gotham-Book' );
    $portraitTitleLabel->add( 'font-size', new CFNumber( 14 ) );
    $portraitTitleLabel->add( 'color', new CFString( isset( $hpub_pad['portrait']['titleLabelColor'] ) ? $hpub_pad['portrait']['titleLabelColor'] : '#A87F52'  );

    $portraitTitleLabel->add( 'margin', $portraitTitleLabelMargin = new CFDictionary() );
    $portraitTitleLabelMargin->add( 'top', new CFNumber( 0 ) );
    $portraitTitleLabelMargin->add( 'right', new CFNumber( 0 ) );
    $portraitTitleLabelMargin->add( 'bottom', new CFNumber( 0 ) );
    $portraitTitleLabelMargin->add( 'left', new CFNumber( 15 ) );

    $padIssuePortrait->add( 'infoLabel', $portraitInfoLabel = new CFDictionary() );
    $portraitInfoLabel->add( 'align', new CFString( isset( $hpub_pad['portrait']['infoLabelAlign'] ) ? $hpub_pad['portrait']['infoLabelAlign'] : 'left'  );
    $portraitInfoLabel->add( 'font', new CFString( 'Gotham-Book' );
    $portraitInfoLabel->add( 'font-size', new CFNumber( 14 ) );
    $portraitInfoLabel->add( 'color', new CFString( isset( $hpub_pad['portrait']['infoLabelColor'] ) ? $hpub_pad['portrait']['infoLabelColor'] : '#A87F52'  );
    $portraitInfoLabel->add( 'lineSpacing', new CFNumber( 4 ) );
    $portraitInfoLabel->add( 'numberOfLines', new CFNumber( 4 ) );

    $portraitTitleLabel->add( 'margin', $portraitInfoLabelMargin = new CFDictionary() );
    $portraitInfoLabelMargin->add( 'top', new CFNumber( 0 ) );
    $portraitInfoLabelMargin->add( 'right', new CFNumber( 0 ) );
    $portraitInfoLabelMargin->add( 'bottom', new CFNumber( 0 ) );
    $portraitInfoLabelMargin->add( 'left', new CFNumber( 15 ) );



    /*
     * Save PList as XML
     */

    $plist->saveXML( PR_CLIENT_SETTINGS_PATH  . $eproject_slug . '.xml.plist' );


    /*
     * Save PList as Binary
     */
    $plist->saveBinary( PR_CLIENT_SETTINGS_PATH  . $eproject_slug . '.binary.plist' );

  }
  /**
   * Get subscription method for editorial project
   *
   * @param  int $term_id
   * @return array or bool
   */
    public static function get_subscription_method( $eproject_id ) {

      $options = get_option( 'taxonomy_term_' . $eproject_id );
      $subscription_types = $options['_pr_subscription_types'];
      $subscription_methods = $options['_pr_subscription_method'];

      if ( isset( $subscription_types ) && !empty( $subscription_types ) ) {
        $methods = array();
        foreach ( $subscription_types as $k => $type ) {
          $identifier = $options['_pr_prefix_bundle_id'] . '.' . $options['_pr_subscription_prefix']. '.' . $type;
          array_push( $methods, $identifier );
        }
      }
      return $methods;
    }
}
 new pressroom_Plist;
?>
