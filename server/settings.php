<?php
/**
 *
 * Create the PropertyList pressroom.plist by using the CFPropertyList API.
 * @package plist
 * @subpackage plist.examples
 */

namespace CFPropertyList;

class pressroom_Plist {

  public function __construct() {

    add_action( 'edited_' . PR_EDITORIAL_PROJECT, array( $this, 'action_get_settings' ), 1, 20 );
    add_action( 'create_' . PR_EDITORIAL_PROJECT, array( $this, 'action_get_settings' ), 1, 20 );
    add_filter( 'pre_update_option_hpub_pad', array( $this, 'action_get_settings' ), 1, 20 );
    add_filter( 'pre_update_option_hpub_phone', array( $this, 'action_get_settings' ), 1, 20 );
  }


  public function action_get_settings( $eproject_id ) {
    
    $eproject = get_term( $eproject_id, PR_EDITORIAL_PROJECT );
    $eproject_slug = $eproject->slug;

    $plist = new CFPropertyList();

    $plist->add( $dict = new CFDictionary() );

    $dict->add( 'isNewsstand', new CFBoolean( true ) );
    $dict->add( 'newsstandLatestIssueCover', new CFBoolean( true ) );
    $dict->add( 'newsstandManifestUrl', new CFString( site_url() . DS . "pressroom-api/shelf/{$eproject_slug}" ) );
    $dict->add( 'purchaseConfirmationUrl', new CFString( site_url() . DS . "pressroom-api/purchaseConfirmationUrl/:app_id/:user_id/{$eproject_slug}" ) );
    $dict->add( 'checkoutUrl', new CFString( site_url() . DS . "pressroom_checkout/:app_id/:user_id/{$eproject_slug}" ) );
    $dict->add( 'purchasesUrl', new CFString( site_url() . DS . "pressroom-api/itunes_purchases_list/:app_id/:user_id/{$eproject_slug}" ) );
    $dict->add( 'postApnsTokenUrl', new CFString( site_url() . DS . "pressroom-api/apns_token/:app_id/:user_id/{$eproject_slug}" ) );
    $dict->add( 'authenticationUrl', new CFString( site_url() . DS . "pressroom-api/authentication/:app_id/:user_id/{$eproject_slug}" ) );
    $dict->add( 'freeSubscriptionProductId', new CFString( "" ));
    $dict->add( 'autoRenewableSubscriptionProductIds', $productIds = new CFArray() );

    $methods = self::_get_subscription_method( $eproject->term_id );
    foreach( $methods as $method ) {
      $productIds->add( new CFString( $method ) );
    }
    $dict->add( 'requestTimeout', new CFNumber( 15 ) );

    /* Pad */
    $dict->add( 'Pad', $pad = new CFDictionary() );

    /* Pad Issue Shelf Shelf */
    $hpub_pad = get_option( 'hpub_pad' );
    $pad->add( 'issuesShelfOptions', $padShelf = new CFDictionary() );
    $padShelf->add( 'layoutType', new CFString( 'grid' ) );
    $padShelf->add( 'gridColumns', new CFNumber( 2 ) );
    $padShelf->add( 'scrollDirection', new CFString( 'vertical' ) );
    $padShelf->add( 'pagingEnabled', new CFBoolean( false ) );
    $padShelf->add( 'backgroundFillStyle', new CFString( isset( $hpub_pad['backgroundFillStyle'] ) ? $hpub_pad['backgroundFillStyle'] : 'Image' ) );
    $padShelf->add( 'backgroundFillGradientStart', new CFString( isset( $hpub_pad['backgroundFillGradientStart'] ) ? $hpub_pad['backgroundFillGradientStart'] : '#FFFFFF' ) );
    $padShelf->add( 'backgroundFillGradientStop', new CFString( isset( $hpub_pad['backgroundFillGradientStop'] ) ? $hpub_pad['backgroundFillGradientStop'] : '#EEEEEE' ) );
    $padShelf->add( 'backgroundFillColor', new CFString( isset( $hpub_pad['backgroundFillColor'] ) ? $hpub_pad['backgroundFillColor'] : '#FFFFFF') );
    $padShelf->add( 'backgroundFitStyle', new CFBoolean( isset( $hpub_pad['backgroundFitStyle'] ) ? $hpub_pad['backgroundFitStyle'] : false ) );
    $padShelf->add( 'backgroundImage', new CFString( isset( $hpub_pad['backgroundImage'] ) ? $hpub_pad['backgroundImage'] : '' ) );
    $padShelf->add( 'headerHidden', new CFBoolean( isset( $hpub_pad['headerHidden'] ) ? $hpub_pad['headerHidden'] : false  ) );
    $padShelf->add( 'headerSticky', new CFBoolean( isset( $hpub_pad['headerSticky'] ) ? $hpub_pad['headerSticky'] : false ) );
    $padShelf->add( 'headerStretch', new CFBoolean( isset( $hpub_pad['headerStretch'] ) ? $hpub_pad['headerStretch'] : false ) );
    $padShelf->add( 'headerBackgroundColor', new CFString( isset( $hpub_pad['headerBackgroundColor'] ) ? $hpub_pad['headerBackgroundColor'] : 'clear' ) );
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
    $portraitTitleLabel->add( 'align', new CFString( isset( $hpub_pad['portrait']['titleLabelAlign'] ) ? $hpub_pad['portrait']['titleLabelAlign'] : 'left'  ) );
    $portraitTitleLabel->add( 'font', new CFString( 'Gotham-Book' ) );
    $portraitTitleLabel->add( 'fontSize', new CFNumber( isset( $hpub_pad['portrait']['titleLabelFontSize'] ) ? $hpub_pad['portrait']['titleLabelFontSize'] : 14 ) );
    $portraitTitleLabel->add( 'color', new CFString( isset( $hpub_pad['portrait']['titleLabelColor'] ) ? $hpub_pad['portrait']['titleLabelColor'] : '#A87F52' ) );

    $portraitTitleLabel->add( 'margin', $portraitTitleLabelMargin = new CFDictionary() );
    $portraitTitleLabelMargin->add( 'top', new CFNumber( 0 ) );
    $portraitTitleLabelMargin->add( 'right', new CFNumber( 0 ) );
    $portraitTitleLabelMargin->add( 'bottom', new CFNumber( 0 ) );
    $portraitTitleLabelMargin->add( 'left', new CFNumber( 15 ) );

    $padIssuePortrait->add( 'infoLabel', $portraitInfoLabel = new CFDictionary() );
    $portraitInfoLabel->add( 'align', new CFString( isset( $hpub_pad['portrait']['infoLabelAlign'] ) ? $hpub_pad['portrait']['infoLabelAlign'] : 'left'  ) );
    $portraitInfoLabel->add( 'font', new CFString( 'Gotham-Book' ) );
    $portraitInfoLabel->add( 'fontSize', new CFNumber( isset( $hpub_pad['portrait']['infoLabelFontSize'] ) ? $hpub_pad['portrait']['infoLabelFontSize'] : 13 ) );
    $portraitInfoLabel->add( 'color', new CFString( isset( $hpub_pad['portrait']['infoLabelColor'] ) ? $hpub_pad['portrait']['infoLabelColor'] : '#8C8C8C' ) );
    $portraitInfoLabel->add( 'lineSpacing', new CFNumber( 4 ) );
    $portraitInfoLabel->add( 'numberOfLines', new CFNumber( 4 ) );

    $portraitInfoLabel->add( 'margin', $portraitInfoLabelMargin = new CFDictionary() );
    $portraitInfoLabelMargin->add( 'top', new CFNumber( 10 ) );
    $portraitInfoLabelMargin->add( 'right', new CFNumber( 0 ) );
    $portraitInfoLabelMargin->add( 'bottom', new CFNumber( 15 ) );
    $portraitInfoLabelMargin->add( 'left', new CFNumber( 15 ) );

    $padIssuePortrait->add( 'actionButton', $portraitActionButton = new CFDictionary() );
    $portraitActionButton->add( 'width', new CFNumber( 110 ) );
    $portraitActionButton->add( 'height', new CFNumber( 30 ) );
    $portraitActionButton->add( 'font', new CFString( 'Gotham-Book' ) );
    $portraitActionButton->add( 'fontSize', new CFNumber( isset( $hpub_pad['portrait']['ActionButtonFontSize'] ) ? $hpub_pad['portrait']['ActionButtonFontSize'] : 13 ) );
    $portraitActionButton->add( 'backgroundColor', new CFString( isset( $hpub_pad['portrait']['ActionButtonBackgroundColor'] ) ? $hpub_pad['portrait']['ActionButtonBackgroundColor'] : '#97724A'  ) );
    $portraitActionButton->add( 'textColor', new CFString( isset( $hpub_pad['portrait']['ActionButtonTextColor'] ) ? $hpub_pad['portrait']['ActionButtonTextColor'] : '#FFFFFF'  ) );

    $padIssuePortrait->add( 'archiveButton', $portraitArchiveButton = new CFDictionary() );
    $portraitArchiveButton->add( 'width', new CFNumber( 30 ) );
    $portraitArchiveButton->add( 'height', new CFNumber( 30 ) );
    $portraitArchiveButton->add( 'font', new CFString( 'Gotham-Book' ) );
    $portraitArchiveButton->add( 'fontSize', new CFNumber( isset( $hpub_pad['portrait']['ArchiveButtonFontSize'] ) ? $hpub_pad['portrait']['ArchiveButtonFontSize'] : 14  ) );
    $portraitArchiveButton->add( 'backgroundColor', new CFString( isset( $hpub_pad['portrait']['ArchiveButtonBackgroundColor'] ) ? $hpub_pad['portrait']['ArchiveButtonBackgroundColor'] : '#A8A8A8'  ) );
    $portraitArchiveButton->add( 'textColor', new CFString( isset( $hpub_pad['portrait']['ArchiveButtonTextColor'] ) ? $hpub_pad['portrait']['ArchiveButtonTextColor'] : '#FFFFFF'  ) );

    $padIssuePortrait->add( 'cover', $portraitCover = new CFDictionary() );
    $portraitCover->add( 'backgroundColor', new CFString( isset( $hpub_pad['portrait']['CoverBackgroundColor'] ) ? $hpub_pad['portrait']['CoverBackgroundColor'] : '#FFFFFF'  ) );
    $portraitCover->add( 'borderColor', new CFString( isset( $hpub_pad['portrait']['BorderColor'] ) ? $hpub_pad['portrait']['BorderColor'] : '#979797'  ) );
    $portraitCover->add( 'borderSize', new CFNumber( 0 ) );
    $portraitCover->add( 'shadowOpacity', new CFNumber( 0 ) );

    $padIssuePortrait->add( 'buttonAlign', new CFString( isset( $hpub_pad['portrait']['buttonAlign'] ) ? $hpub_pad['portrait']['buttonAlign'] : 'left'  ) );

    $padIssuePortrait->add( 'buttonMargin', $padIssuePortraitMargin = new CFDictionary() );
    $padIssuePortraitMargin->add( 'top', new CFNumber( 0 ) );
    $padIssuePortraitMargin->add( 'right', new CFNumber( 0 ) );
    $padIssuePortraitMargin->add( 'bottom', new CFNumber( 0 ) );
    $padIssuePortraitMargin->add( 'left', new CFNumber( 15 ) );

    $padIssuePortrait->add( 'priceColor', new CFString( isset( $hpub_pad['portrait']['priceColor'] ) ? $hpub_pad['portrait']['priceColor'] : '#FFFFFF'  ) );
    $padIssuePortrait->add( 'loadingLabelColor', new CFString( isset( $hpub_pad['portrait']['loadingLabelColor'] ) ? $hpub_pad['portrait']['loadingLabelColor'] : '#A87F52'  ) );
    $padIssuePortrait->add( 'loadingLabelFont', new CFString( 'Gotham-Book' ) );
    $padIssuePortrait->add( 'loadingLabelFontSize', new CFNumber( isset( $hpub_pad['portrait']['loadingLabelFontSize'] ) ? $hpub_pad['portrait']['loadingLabelFontSize'] : 11  ) );
    $padIssuePortrait->add( 'loadingSpinnerColor', new CFString( isset( $hpub_pad['portrait']['loadingSpinnerColor'] ) ? $hpub_pad['portrait']['loadingSpinnerColor'] : '#A87F52'  ) );
    $padIssuePortrait->add( 'progressBarTintColor', new CFString( isset( $hpub_pad['portrait']['progressBarTintColor'] ) ? $hpub_pad['portrait']['progressBarTintColor'] : '#A87F52'  ) );
    $padIssuePortrait->add( 'progressBarBackgroundColor', new CFString( isset( $hpub_pad['portrait']['progressBarBackgroundColor'] ) ? $hpub_pad['portrait']['progressBarBackgroundColor'] : '#DDDDDD'  ) );

    /* Landscape */
    $padIssue->add( 'landscape', $padIssueLandscape = new CFDictionary() );
    $padIssueLandscape->add( 'cellHeight', new CFNumber( 230 ) );

    $padIssueLandscape->add( 'cellPadding', $landscapeCellpadding = new CFDictionary() );
    $landscapeCellpadding->add( 'top', new CFNumber( 50 ) );
    $landscapeCellpadding->add( 'right', new CFNumber( 25 ) );
    $landscapeCellpadding->add( 'bottom', new CFNumber( 0 ) );
    $landscapeCellpadding->add( 'left', new CFNumber( 25 ) );

    $padIssueLandscape->add( 'titleLabel', $landscapeTitleLabel = new CFDictionary() );
    $landscapeTitleLabel->add( 'align', new CFString( isset( $hpub_pad['landscape']['titleLabelAlign'] ) ? $hpub_pad['landscape']['titleLabelAlign'] : 'left'  ) );
    $landscapeTitleLabel->add( 'font', new CFString( 'Gotham-Book' ) );
    $landscapeTitleLabel->add( 'fontSize', new CFNumber( isset( $hpub_pad['landscape']['titleLabelFontSize'] ) ? $hpub_pad['landscape']['titleLabelFontSize'] : 14 ) );
    $landscapeTitleLabel->add( 'color', new CFString( isset( $hpub_pad['landscape']['titleLabelColor'] ) ? $hpub_pad['landscape']['titleLabelColor'] : '#A87F52' ) );

    $landscapeTitleLabel->add( 'margin', $landscapeTitleLabelMargin = new CFDictionary() );
    $landscapeTitleLabelMargin->add( 'top', new CFNumber( 0 ) );
    $landscapeTitleLabelMargin->add( 'right', new CFNumber( 0 ) );
    $landscapeTitleLabelMargin->add( 'bottom', new CFNumber( 0 ) );
    $landscapeTitleLabelMargin->add( 'left', new CFNumber( 15 ) );

    $padIssueLandscape->add( 'infoLabel', $landscapeInfoLabel = new CFDictionary() );
    $landscapeInfoLabel->add( 'align', new CFString( isset( $hpub_pad['landscape']['infoLabelAlign'] ) ? $hpub_pad['landscape']['infoLabelAlign'] : 'left'  ) );
    $landscapeInfoLabel->add( 'font', new CFString( 'Gotham-Book' ) );
    $landscapeInfoLabel->add( 'fontSize', new CFNumber( isset( $hpub_pad['landscape']['infoLabelFontSize'] ) ? $hpub_pad['landscape']['infoLabelFontSize'] : 13 ) );
    $landscapeInfoLabel->add( 'color', new CFString( isset( $hpub_pad['landscape']['infoLabelColor'] ) ? $hpub_pad['landscape']['infoLabelColor'] : '#8C8C8C' ) );
    $landscapeInfoLabel->add( 'lineSpacing', new CFNumber( 5 ) );
    $landscapeInfoLabel->add( 'numberOfLines', new CFNumber( 4 ) );

    $landscapeInfoLabel->add( 'margin', $landscapeInfoLabelMargin = new CFDictionary() );
    $landscapeInfoLabelMargin->add( 'top', new CFNumber( 10 ) );
    $landscapeInfoLabelMargin->add( 'right', new CFNumber( 0 ) );
    $landscapeInfoLabelMargin->add( 'bottom', new CFNumber( 15 ) );
    $landscapeInfoLabelMargin->add( 'left', new CFNumber( 15 ) );

    $padIssueLandscape->add( 'actionButton', $landscapeActionButton = new CFDictionary() );
    $landscapeActionButton->add( 'width', new CFNumber( 110 ) );
    $landscapeActionButton->add( 'height', new CFNumber( 30 ) );
    $landscapeActionButton->add( 'font', new CFString( 'Gotham-Book' ) );
    $landscapeActionButton->add( 'fontSize', new CFNumber( isset( $hpub_pad['landscape']['ActionButtonFontSize'] ) ? $hpub_pad['landscape']['ActionButtonFontSize'] : 14  ) );
    $landscapeActionButton->add( 'backgroundColor', new CFString( isset( $hpub_pad['landscape']['ActionButtonBackgroundColor'] ) ? $hpub_pad['landscape']['ActionButtonBackgroundColor'] : '#97724A'  ) );
    $landscapeActionButton->add( 'textColor', new CFString( isset( $hpub_pad['landscape']['ActionButtonTextColor'] ) ? $hpub_pad['landscape']['ActionButtonTextColor'] : '#FFFFFF'  ) );

    $padIssueLandscape->add( 'archiveButton', $landscapeArchiveButton = new CFDictionary() );
    $landscapeArchiveButton->add( 'width', new CFNumber( 30 ) );
    $landscapeArchiveButton->add( 'height', new CFNumber( 30 ) );
    $landscapeArchiveButton->add( 'font', new CFString( 'Gotham-Book' ) );
    $landscapeArchiveButton->add( 'fontSize', new CFNumber( isset( $hpub_pad['landscape']['ArchiveButtonFontSize'] ) ? $hpub_pad['landscape']['ArchiveButtonFontSize'] : 14 ) );
    $landscapeArchiveButton->add( 'backgroundColor', new CFString( isset( $hpub_pad['landscape']['ArchiveButtonBackgroundColor'] ) ? $hpub_pad['landscape']['ArchiveButtonBackgroundColor'] : '#A8A8A8'  ) );
    $landscapeArchiveButton->add( 'textColor', new CFString( isset( $hpub_pad['landscape']['ArchiveButtonTextColor'] ) ? $hpub_pad['landscape']['ArchiveButtonTextColor'] : '#FFFFFF'  ) );

    $padIssueLandscape->add( 'cover', $landscapeCover = new CFDictionary() );
    $landscapeCover->add( 'backgroundColor', new CFString( isset( $hpub_pad['landscape']['CoverBackgroundColor'] ) ? $hpub_pad['landscape']['CoverBackgroundColor'] : '#FFFFFF'  ) );
    $landscapeCover->add( 'borderColor', new CFString( isset( $hpub_pad['landscape']['BorderColor'] ) ? $hpub_pad['landscape']['BorderColor'] : '#979797'  ) );
    $landscapeCover->add( 'borderSize', new CFNumber( 0 ) );
    $landscapeCover->add( 'shadowOpacity', new CFNumber( 0 ) );

    $padIssueLandscape->add( 'buttonAlign', new CFString( isset( $hpub_pad['landscape']['buttonAlign'] ) ? $hpub_pad['landscape']['buttonAlign'] : 'left'  ) );

    $padIssueLandscape->add( 'buttonMargin', $padIssueLandscapeMargin = new CFDictionary() );
    $padIssueLandscapeMargin->add( 'top', new CFNumber( 0 ) );
    $padIssueLandscapeMargin->add( 'right', new CFNumber( 0 ) );
    $padIssueLandscapeMargin->add( 'bottom', new CFNumber( 0 ) );
    $padIssueLandscapeMargin->add( 'left', new CFNumber( 15 ) );

    $padIssueLandscape->add( 'priceColor', new CFString( isset( $hpub_pad['landscape']['priceColor'] ) ? $hpub_pad['landscape']['priceColor'] : '#FFFFFF'  ) );
    $padIssueLandscape->add( 'loadingLabelColor', new CFString( isset( $hpub_pad['landscape']['loadingLabelColor'] ) ? $hpub_pad['landscape']['loadingLabelColor'] : '#A87F52'  ) );
    $padIssueLandscape->add( 'loadingLabelFont', new CFString( 'Gotham-Book' ) );
    $padIssueLandscape->add( 'loadingLabelFontSize', new CFNumber( isset( $hpub_pad['landscape']['loadingLabelFontSize'] ) ? $hpub_pad['landscape']['loadingLabelFontSize'] : 11  ) );
    $padIssueLandscape->add( 'loadingSpinnerColor', new CFString( isset( $hpub_pad['landscape']['loadingSpinnerColor'] ) ? $hpub_pad['landscape']['loadingSpinnerColor'] : '#A87F52'  ) );
    $padIssueLandscape->add( 'progressBarTintColor', new CFString( isset( $hpub_pad['landscape']['progressBarTintColor'] ) ? $hpub_pad['landscape']['progressBarTintColor'] : '#A87F52'  ) );
    $padIssueLandscape->add( 'progressBarBackgroundColor', new CFString( isset( $hpub_pad['landscape']['progressBarBackgroundColor'] ) ? $hpub_pad['landscape']['progressBarBackgroundColor'] : '#DDDDDD'  ) );

    $pad->add( 'navigationBarOptions', $padnavigationBarOptions = new CFDictionary() );

    $padnavigationBarOptions->add( 'book', $padNavBook = new CFDictionary() );
    $padNavBook->add( 'tintColor', new CFString( isset( $hpub_pad['navigationBarOptionBook']['tintColor'] ) ? $hpub_pad['navigationBarOptionBook']['tintColor'] : '#97724A'  ) );
    $padNavBook->add( 'titleFontSize', new CFNumber( isset( $hpub_pad['navigationBarOptionBook']['titleFontSize'] ) ? $hpub_pad['navigationBarOptionBook']['titleFontSize'] : 18  ) );
    $padNavBook->add( 'titleFont', new CFString( 'Gotham-Book' ) );
    $padNavBook->add( 'titleColor', new CFString( isset( $hpub_pad['navigationBarOptionBook']['titleColor'] ) ? $hpub_pad['navigationBarOptionBook']['titleColor'] : '#97724A'  ) );
    $padNavBook->add( 'backgroundColor', new CFString( isset( $hpub_pad['navigationBarOptionBook']['backgroundColor'] ) ? $hpub_pad['navigationBarOptionBook']['backgroundColor'] : '#FFFFFF'  ) );
    $padNavBook->add( 'marginBottom', new CFNumber( 60 ) );

    $padnavigationBarOptions->add( 'shelf', $padNavShelf = new CFDictionary() );
    $padNavShelf->add( 'tintColor', new CFString( isset( $hpub_pad['navigationBarOptionShelf']['tintColor'] ) ? $hpub_pad['navigationBarOptionShelf']['tintColor'] : '#A99870'  ) );
    $padNavShelf->add( 'titleFontSize', new CFNumber( isset( $hpub_pad['navigationBarOptionShelf']['titleFontSize'] ) ? $hpub_pad['navigationBarOptionShelf']['titleFontSize'] : 16  ) );
    $padNavShelf->add( 'titleFont', new CFString( 'Gotham-Book' ) );
    $padNavShelf->add( 'titleColor', new CFString( isset( $hpub_pad['navigationBarOptionShelf']['titleColor'] ) ? $hpub_pad['navigationBarOptionShelf']['titleColor'] : '#A99870'  ) );
    $padNavShelf->add( 'backgroundColor', new CFString( isset( $hpub_pad['navigationBarOptionShelf']['backgroundColor'] ) ? $hpub_pad['navigationBarOptionShelf']['backgroundColor'] : '#FFFFFF'  ) );
    $padNavShelf->add( 'marginBottom', new CFNumber( 0 ) );

    $pad->add( 'subscriptionViewOptions', $padSubscriptionViewOptions = new CFDictionary() );
    $padSubscriptionViewOptions->add( 'useNativeControl', new CFBoolean( false ) );
    $padSubscriptionViewOptions->add( 'backgroundColor', new CFString( isset( $hpub_pad['subscriptionViewOption']['backgroundColor'] ) ? $hpub_pad['subscriptionViewOption']['backgroundColor'] : '#FFFFFF'  ) );
    $padSubscriptionViewOptions->add( 'buttonWidth', new CFNumber( 0 ) );
    $padSubscriptionViewOptions->add( 'buttonHeight', new CFNumber( 60 ) );
    $padSubscriptionViewOptions->add( 'buttonTintColor', new CFString( isset( $hpub_pad['subscriptionViewOption']['buttonTintColor'] ) ? $hpub_pad['subscriptionViewOption']['buttonTintColor'] : '#999999'  ) );
    $padSubscriptionViewOptions->add( 'buttonTintColorHighlighted', new CFString( isset( $hpub_pad['subscriptionViewOption']['buttonTintColorHighlighted'] ) ? $hpub_pad['subscriptionViewOption']['buttonTintColorHighlighted'] : '#A99870'  ) );
    $padSubscriptionViewOptions->add( 'buttonBackgroundColor', new CFString( isset( $hpub_pad['subscriptionViewOption']['buttonBackgroundColor'] ) ? $hpub_pad['subscriptionViewOption']['buttonBackgroundColor'] : '#FFFFFF'  ) );

    $padSubscriptionViewOptions->add( 'margin', $padSubscriptionViewOptionsMargin = new CFDictionary() );
    $padSubscriptionViewOptionsMargin->add( 'top', new CFNumber( 10 ) );
    $padSubscriptionViewOptionsMargin->add( 'right', new CFNumber( 30 ) );
    $padSubscriptionViewOptionsMargin->add( 'bottom', new CFNumber( 10 ) );
    $padSubscriptionViewOptionsMargin->add( 'left', new CFNumber( 30 ) );

    $padSubscriptionViewOptions->add( 'buttonFont', new CFString( 'Lato-Regular' ) );
    $padSubscriptionViewOptions->add( 'buttonFontSize', new CFNumber( isset( $hpub_pad['subscriptionViewOption']['buttonFontSize'] ) ? $hpub_pad['subscriptionViewOption']['buttonFontSize'] : 21  ) );
    $padSubscriptionViewOptions->add( 'separatorColor', new CFString( isset( $hpub_pad['subscriptionViewOption']['SeparatorColor'] ) ? $hpub_pad['subscriptionViewOption']['separatorColor'] : '#D8D8D8'  ) );
    $padSubscriptionViewOptions->add( 'separatorHeight', new CFNumber( 1 ) );

    $pad->add( 'authenticationViewOptions', $padAuthenticationViewOptions = new CFDictionary() );
    $padAuthenticationViewOptions->add( 'backgroundColor', new CFString( isset( $hpub_pad['authenticationViewOptions']['backgroundColor'] ) ? $hpub_pad['authenticationViewOptions']['backgroundColor'] : '#FFFFFF'  ) );
    $padAuthenticationViewOptions->add( 'fieldWidth', new CFNumber( 0 ) );
    $padAuthenticationViewOptions->add( 'fieldHeight', new CFNumber( 40 ) );
    $padAuthenticationViewOptions->add( 'fieldTextColor', new CFString( isset( $hpub_pad['authenticationViewOptions']['fieldTextColor'] ) ? $hpub_pad['authenticationViewOptions']['fieldTextColor'] : '#444444'  ) );
    $padAuthenticationViewOptions->add( 'fieldFont', new CFString( 'Cardo-Italic' ) );
    $padAuthenticationViewOptions->add( 'fieldFontSize', new CFNumber( isset( $hpub_pad['authenticationViewOptions']['fieldFontSize'] ) ? $hpub_pad['authenticationViewOptions']['fieldFontSize'] : 23 ) );

    $padAuthenticationViewOptions->add( 'fieldMargin', $padAuthenticationViewOptionsMargin = new CFDictionary() );
    $padAuthenticationViewOptionsMargin->add( 'top', new CFNumber( 10 ) );
    $padAuthenticationViewOptionsMargin->add( 'right', new CFNumber( 30 ) );
    $padAuthenticationViewOptionsMargin->add( 'bottom', new CFNumber( 10 ) );
    $padAuthenticationViewOptionsMargin->add( 'left', new CFNumber( 30 ) );

    $padAuthenticationViewOptions->add( 'fieldPlaceholderFont', new CFString( 'Cardo-Italic' ) );
    $padAuthenticationViewOptions->add( 'fieldPlaceholderColor', new CFString( isset( $hpub_pad['authenticationViewOptions']['fieldPlaceholderColor'] ) ? $hpub_pad['authenticationViewOptions']['fieldPlaceholderColor'] : '#999999'  ) );
    $padAuthenticationViewOptions->add( 'buttonFont', new CFString( 'Lato-Regular' ) );
    $padAuthenticationViewOptions->add( 'buttonFontSize', new CFNumber( isset( $hpub_pad['authenticationViewOptions']['buttonFontSize'] ) ? $hpub_pad['authenticationViewOptions']['buttonFontSize'] : 17 ) );
    $padAuthenticationViewOptions->add( 'buttonTintColor', new CFString( isset( $hpub_pad['authenticationViewOptions']['buttonTintColor'] ) ? $hpub_pad['authenticationViewOptions']['buttonTintColor'] : '#444444'  ) );
    $padAuthenticationViewOptions->add( 'buttonTintColorHighlighted', new CFString( isset( $hpub_pad['authenticationViewOptions']['buttonTintColorHighlighted'] ) ? $hpub_pad['authenticationViewOptions']['buttonTintColorHighlighted'] : '#A99870'  ) );
    $padAuthenticationViewOptions->add( 'buttonBackgroundColor', new CFString( isset( $hpub_pad['authenticationViewOptions']['buttonBackgroundColor'] ) ? $hpub_pad['authenticationViewOptions']['buttonBackgroundColor'] : '#FFFFFF'  ) );
    $padAuthenticationViewOptions->add( 'buttonBorderSize', new CFNumber( 1 ) );
    $padAuthenticationViewOptions->add( 'buttonBorderColor', new CFString( isset( $hpub_pad['authenticationViewOptions']['buttonBorderColor'] ) ? $hpub_pad['authenticationViewOptions']['buttonBorderColor'] : '#A6A6A6'  ) );
    $padAuthenticationViewOptions->add( 'labelTextColor', new CFString( isset( $hpub_pad['authenticationViewOptions']['labelTextColor'] ) ? $hpub_pad['authenticationViewOptions']['labelTextColor'] : '#444444'  ) );
    $padAuthenticationViewOptions->add( 'labelFont', new CFString( 'Lato-Regular' ) );
    $padAuthenticationViewOptions->add( 'labelFontSize', new CFNumber( isset( $hpub_pad['authenticationViewOptions']['labelFontSize'] ) ? $hpub_pad['authenticationViewOptions']['labelFontSize'] : 14  ) );
    $padAuthenticationViewOptions->add( 'separatorColor', new CFString( isset( $hpub_pad['authenticationViewOptions']['separatorColor'] ) ? $hpub_pad['authenticationViewOptions']['separatorColor'] : '#D8D8D8'  ) );
    $padAuthenticationViewOptions->add( 'separatorHeight', new CFNumber( 1 ) );

    /* Phone */
    $dict->add( 'Phone', $phone = new CFDictionary() );

    /* Phone Issue Shelf Shelf */
    $hpub_phone = get_option( 'hpub_phone' );
    $phone->add( 'issuesShelfOptions', $phoneShelf = new CFDictionary() );
    $phoneShelf->add( 'layoutType', new CFString( 'coverflow' ) );
    $phoneShelf->add( 'gridColumns', new CFNumber( 1 ) );
    $phoneShelf->add( 'scrollDirection', new CFString( 'horizontal' ) );
    $phoneShelf->add( 'pagingEnabled', new CFBoolean( true ) );
    $phoneShelf->add( 'backgroundFillStyle', new CFString( isset( $hpub_phone['backgroundFillStyle'] ) ? $hpub_phone['backgroundFillStyle'] : 'Image' ) );
    $phoneShelf->add( 'backgroundFillGradientStart', new CFString( isset( $hpub_phone['backgroundFillGradientStart'] ) ? $hpub_phone['backgroundFillGradientStart'] : '#FFFFFF' ) );
    $phoneShelf->add( 'backgroundFillGradientStop', new CFString( isset( $hpub_phone['backgroundFillGradientStop'] ) ? $hpub_phone['backgroundFillGradientStop'] : '#EEEEEE' ) );
    $phoneShelf->add( 'backgroundFillColor', new CFString( isset( $hpub_phone['backgroundFillColor'] ) ? $hpub_phone['backgroundFillColor'] : '#FFFFFF') );
    $phoneShelf->add( 'backgroundFitStyle', new CFBoolean( isset( $hpub_phone['backgroundFitStyle'] ) ? $hpub_phone['backgroundFitStyle'] : false ) );
    $phoneShelf->add( 'headerHidden', new CFBoolean( isset( $hpub_phone['headerHidden'] ) ? $hpub_phone['headerHidden'] : true  ) );
    $phoneShelf->add( 'headerSticky', new CFBoolean( isset( $hpub_phone['headerSticky'] ) ? $hpub_phone['headerSticky'] : false ) );
    $phoneShelf->add( 'headerStretch', new CFBoolean( isset( $hpub_phone['headerStretch'] ) ? $hpub_phone['headerStretch'] : false ) );
    $phoneShelf->add( 'headerBackgroundColor', new CFString( isset( $hpub_phone['headerBackgroundColor'] ) ? $hpub_phone['headerBackgroundColor'] : 'clear' ) );
    $phoneShelf->add( 'headerImageFill', new CFBoolean( isset( $hpub_phone['headerImageFill'] ) ? $hpub_phone['headerImageFill'] : false ) );
    $phoneShelf->add( 'headerHeightLandscape', new CFNumber( 150 ) );
    $phoneShelf->add( 'headerHeightPortrait', new CFNumber( 150 ) );

    /* Phone Issue Option */
    $phone->add( 'issuesOptions', $phoneIssue = new CFDictionary() );

    /* Portrait */
    $phoneIssue->add( 'portrait', $phoneIssuePortrait = new CFDictionary() );
    $phoneIssuePortrait->add( 'cellHeight', new CFNumber( 0 ) );

    $phoneIssuePortrait->add( 'cellPadding', $portraitCellpadding = new CFDictionary() );
    $portraitCellpadding->add( 'top', new CFNumber( 15 ) );
    $portraitCellpadding->add( 'right', new CFNumber( 0 ) );
    $portraitCellpadding->add( 'bottom', new CFNumber( 0 ) );
    $portraitCellpadding->add( 'left', new CFNumber( 0 ) );

    $phoneIssuePortrait->add( 'titleLabel', $portraitTitleLabel = new CFDictionary() );
    $portraitTitleLabel->add( 'align', new CFString( isset( $hpub_phone['portrait']['titleLabelAlign'] ) ? $hpub_phone['portrait']['titleLabelAlign'] : 'center'  ) );
    $portraitTitleLabel->add( 'font', new CFString( 'Lato-Regular' ) );
    $portraitTitleLabel->add( 'fontSize', new CFNumber( isset( $hpub_phone['portrait']['titleLabelFontSize'] ) ? $hpub_phone['portrait']['titleLabelFontSize'] : 17  ) );
    $portraitTitleLabel->add( 'color', new CFString( isset( $hpub_phone['portrait']['titleLabelColor'] ) ? $hpub_phone['portrait']['titleLabelColor'] : '#A99870' ) );

    $portraitTitleLabel->add( 'margin', $portraitTitleLabelMargin = new CFDictionary() );
    $portraitTitleLabelMargin->add( 'top', new CFNumber( 15 ) );
    $portraitTitleLabelMargin->add( 'right', new CFNumber( 0 ) );
    $portraitTitleLabelMargin->add( 'bottom', new CFNumber( 0 ) );
    $portraitTitleLabelMargin->add( 'left', new CFNumber( 0 ) );

    $phoneIssuePortrait->add( 'infoLabel', $portraitInfoLabel = new CFDictionary() );
    $portraitInfoLabel->add( 'align', new CFString( isset( $hpub_phone['portrait']['infoLabelAlign'] ) ? $hpub_phone['portrait']['infoLabelAlign'] : 'center'  ) );
    $portraitInfoLabel->add( 'font', new CFString( 'Lato-Regular' ) );
    $portraitInfoLabel->add( 'fontSize', new CFNumber( isset( $hpub_phone['portrait']['infoLabelFontSize'] ) ? $hpub_phone['portrait']['infoLabelFontSize'] : 14  ) );
    $portraitInfoLabel->add( 'color', new CFString( isset( $hpub_phone['portrait']['infoLabelColor'] ) ? $hpub_phone['portrait']['infoLabelColor'] : '#8C8C8C' ) );
    $portraitInfoLabel->add( 'lineSpacing', new CFNumber( 5 ) );
    $portraitInfoLabel->add( 'numberOfLines', new CFNumber( 4 ) );

    $portraitTitleLabel->add( 'margin', $portraitInfoLabelMargin = new CFDictionary() );
    $portraitInfoLabelMargin->add( 'top', new CFNumber( 10 ) );
    $portraitInfoLabelMargin->add( 'right', new CFNumber( 15 ) );
    $portraitInfoLabelMargin->add( 'bottom', new CFNumber( 15 ) );
    $portraitInfoLabelMargin->add( 'left', new CFNumber( 15 ) );

    $phoneIssuePortrait->add( 'actionButton', $portraitActionButton = new CFDictionary() );
    $portraitActionButton->add( 'width', new CFNumber( 140 ) );
    $portraitActionButton->add( 'height', new CFNumber( 40 ) );
    $portraitActionButton->add( 'font', new CFString( 'Lato-Regular' ) );
    $portraitActionButton->add( 'fontSize', new CFNumber( isset( $hpub_phone['portrait']['ActionButtonFontSize'] ) ? $hpub_phone['portrait']['ActionButtonFontSize'] : 17  ) );
    $portraitActionButton->add( 'backgroundColor', new CFString( isset( $hpub_phone['portrait']['ActionButtonBackgroundColor'] ) ? $hpub_phone['portrait']['ActionButtonBackgroundColor'] : '#A99870'  ) );
    $portraitActionButton->add( 'textColor', new CFString( isset( $hpub_phone['portrait']['ActionButtonTextColor'] ) ? $hpub_phone['portrait']['ActionButtonTextColor'] : '#FFFFFF'  ) );

    $phoneIssuePortrait->add( 'archiveButton', $portraitArchiveButton = new CFDictionary() );
    $portraitArchiveButton->add( 'width', new CFNumber( 40 ) );
    $portraitArchiveButton->add( 'height', new CFNumber( 40 ) );
    $portraitArchiveButton->add( 'font', new CFString( 'Lato-Light' ) );
    $portraitArchiveButton->add( 'fontSize', new CFNumber( isset( $hpub_phone['portrait']['ArchiveButtonFontSize'] ) ? $hpub_phone['portrait']['ArchiveButtonFontSize'] : 17  ) );
    $portraitArchiveButton->add( 'backgroundColor', new CFString( isset( $hpub_phone['portrait']['ArchiveButtonBackgroundColor'] ) ? $hpub_phone['portrait']['ArchiveButtonBackgroundColor'] : '#A8A8A8'  ) );
    $portraitArchiveButton->add( 'textColor', new CFString( isset( $hpub_phone['portrait']['ArchiveButtonTextColor'] ) ? $hpub_phone['portrait']['ArchiveButtonTextColor'] : '#FFFFFF'  ) );

    $phoneIssuePortrait->add( 'cover', $portraitCover = new CFDictionary() );
    $portraitCover->add( 'backgroundColor', new CFString( isset( $hpub_phone['portrait']['CoverBackgroundColor'] ) ? $hpub_phone['portrait']['CoverBackgroundColor'] : '#FFFFFF'  ) );
    $portraitCover->add( 'borderColor', new CFString( isset( $hpub_phone['portrait']['BorderColor'] ) ? $hpub_phone['portrait']['BorderColor'] : '#979797'  ) );
    $portraitCover->add( 'borderSize', new CFNumber( 0 ) );
    $portraitCover->add( 'shadowOpacity', new CFNumber( 0 ) );

    $phoneIssuePortrait->add( 'buttonAlign', new CFString( isset( $hpub_phone['portrait']['buttonAlign'] ) ? $hpub_phone['portrait']['buttonAlign'] : 'center'  ) );

    $phoneIssuePortrait->add( 'buttonMargin', $phoneIssuePortraitMargin = new CFDictionary() );
    $phoneIssuePortraitMargin->add( 'top', new CFNumber( 10 ) );
    $phoneIssuePortraitMargin->add( 'right', new CFNumber( 10 ) );
    $phoneIssuePortraitMargin->add( 'bottom', new CFNumber( 30 ) );
    $phoneIssuePortraitMargin->add( 'left', new CFNumber( 10 ) );

    $phoneIssuePortrait->add( 'priceColor', new CFString( isset( $hpub_phone['portrait']['priceColor'] ) ? $hpub_phone['portrait']['priceColor'] : '#8c8c8c'  ) );
    $phoneIssuePortrait->add( 'loadingLabelColor', new CFString( isset( $hpub_phone['portrait']['loadingLabelColor'] ) ? $hpub_phone['portrait']['loadingLabelColor'] : '#97724a'  ) );
    $phoneIssuePortrait->add( 'loadingLabelFont', new CFString( 'Lato-Regular' ) );
    $phoneIssuePortrait->add( 'loadingLabelFontSize', new CFNumber( isset( $hpub_phone['portrait']['loadingLabelFontSize'] ) ? $hpub_phone['portrait']['loadingLabelFontSize'] : 11  )  );
    $phoneIssuePortrait->add( 'loadingSpinnerColor', new CFString( isset( $hpub_phone['portrait']['loadingSpinnerColor'] ) ? $hpub_phone['portrait']['loadingSpinnerColor'] : '#929292'  ) );
    $phoneIssuePortrait->add( 'progressBarTintColor', new CFString( isset( $hpub_phone['portrait']['progressBarTintColor'] ) ? $hpub_phone['portrait']['progressBarTintColor'] : '#97724a'  ) );
    $phoneIssuePortrait->add( 'progressBarBackgroundColor', new CFString( isset( $hpub_phone['portrait']['progressBarBackgroundColor'] ) ? $hpub_phone['portrait']['progressBarBackgroundColor'] : '#DDDDDD'  ) );

    /* Landscape */
    $phoneIssue->add( 'landscape', $phoneIssueLandscape = new CFDictionary() );
    $phoneIssueLandscape->add( 'cellHeight', new CFNumber( 0 ) );

    $phoneIssueLandscape->add( 'cellPadding', $landscapeCellpadding = new CFDictionary() );
    $landscapeCellpadding->add( 'top', new CFNumber( 20 ) );
    $landscapeCellpadding->add( 'right', new CFNumber( 15 ) );
    $landscapeCellpadding->add( 'bottom', new CFNumber( 0 ) );
    $landscapeCellpadding->add( 'left', new CFNumber( 15 ) );

    $phoneIssueLandscape->add( 'titleLabel', $landscapeTitleLabel = new CFDictionary() );
    $landscapeTitleLabel->add( 'align', new CFString( isset( $hpub_phone['landscape']['titleLabelAlign'] ) ? $hpub_phone['landscape']['titleLabelAlign'] : 'left'  ) );
    $landscapeTitleLabel->add( 'font', new CFString( 'Lato-Regular' ) );
    $landscapeTitleLabel->add( 'fontSize', new CFNumber( isset( $hpub_phone['landscape']['titleLabelFontSize'] ) ? $hpub_phone['landscape']['titleLabelFontSize'] : 17 ) );
    $landscapeTitleLabel->add( 'color', new CFString( isset( $hpub_phone['landscape']['titleLabelColor'] ) ? $hpub_phone['landscape']['titleLabelColor'] : '#A99870' ) );

    $landscapeTitleLabel->add( 'margin', $landscapeTitleLabelMargin = new CFDictionary() );
    $landscapeTitleLabelMargin->add( 'top', new CFNumber( 0 ) );
    $landscapeTitleLabelMargin->add( 'right', new CFNumber( 15 ) );
    $landscapeTitleLabelMargin->add( 'bottom', new CFNumber( 0 ) );
    $landscapeTitleLabelMargin->add( 'left', new CFNumber( 15 ) );

    $phoneIssueLandscape->add( 'infoLabel', $landscapeInfoLabel = new CFDictionary() );
    $landscapeInfoLabel->add( 'align', new CFString( isset( $hpub_phone['landscape']['infoLabelAlign'] ) ? $hpub_phone['landscape']['infoLabelAlign'] : 'left'  ) );
    $landscapeInfoLabel->add( 'font', new CFString( 'Lato-Regular' ) );
    $landscapeInfoLabel->add( 'fontSize', new CFNumber( isset( $hpub_phone['landscape']['infoLabelfontSize'] ) ? $hpub_phone['landscape']['infoLabelfontSize'] : 14 ) );
    $landscapeInfoLabel->add( 'color', new CFString( isset( $hpub_phone['landscape']['infoLabelColor'] ) ? $hpub_phone['landscape']['infoLabelColor'] : '#8C8C8C' ) );
    $landscapeInfoLabel->add( 'lineSpacing', new CFNumber( 5 ) );
    $landscapeInfoLabel->add( 'numberOfLines', new CFNumber( 5 ) );

    $landscapeInfoLabel->add( 'margin', $landscapeInfoLabelMargin = new CFDictionary() );
    $landscapeInfoLabelMargin->add( 'top', new CFNumber( 10 ) );
    $landscapeInfoLabelMargin->add( 'right', new CFNumber( 15 ) );
    $landscapeInfoLabelMargin->add( 'bottom', new CFNumber( 15 ) );
    $landscapeInfoLabelMargin->add( 'left', new CFNumber( 15 ) );

    $phoneIssueLandscape->add( 'actionButton', $landscapeActionButton = new CFDictionary() );
    $landscapeActionButton->add( 'width', new CFNumber( 140 ) );
    $landscapeActionButton->add( 'height', new CFNumber( 40 ) );
    $landscapeActionButton->add( 'font', new CFString( 'Lato-Regular' ) );
    $landscapeActionButton->add( 'fontSize', new CFNumber( isset( $hpub_phone['landscape']['ActionButtonFontSize'] ) ? $hpub_phone['landscape']['ActionButtonFontSize'] : 17  ) );
    $landscapeActionButton->add( 'backgroundColor', new CFString( isset( $hpub_phone['landscape']['ActionButtonBackgroundColor'] ) ? $hpub_phone['landscape']['ActionButtonBackgroundColor'] : '#A99870'  ) );
    $landscapeActionButton->add( 'textColor', new CFString( isset( $hpub_phone['landscape']['ActionButtonTextColor'] ) ? $hpub_phone['landscape']['ActionButtonTextColor'] : '#FFFFFF'  ) );

    $phoneIssueLandscape->add( 'archiveButton', $landscapeArchiveButton = new CFDictionary() );
    $landscapeArchiveButton->add( 'width', new CFNumber( 40 ) );
    $landscapeArchiveButton->add( 'height', new CFNumber( 40 ) );
    $landscapeArchiveButton->add( 'font', new CFString( 'Lato-Regular' ) );
    $landscapeArchiveButton->add( 'fontSize', new CFNumber( isset( $hpub_phone['landscape']['ArchiveButtonFontSize'] ) ? $hpub_phone['landscape']['ArchiveButtonFontSize'] : 17  ) );
    $landscapeArchiveButton->add( 'backgroundColor', new CFString( isset( $hpub_phone['landscape']['ArchiveButtonBackgroundColor'] ) ? $hpub_phone['landscape']['ArchiveButtonBackgroundColor'] : '#A8A8A8'  ) );
    $landscapeArchiveButton->add( 'textColor', new CFString( isset( $hpub_phone['landscape']['ArchiveButtonTextColor'] ) ? $hpub_phone['landscape']['ArchiveButtonTextColor'] : '#FFFFFF'  ) );

    $phoneIssueLandscape->add( 'cover', $landscapeCover = new CFDictionary() );
    $landscapeCover->add( 'backgroundColor', new CFString( isset( $hpub_phone['landscape']['CoverBackgroundColor'] ) ? $hpub_phone['landscape']['CoverBackgroundColor'] : '#FFFFFF'  ) );
    $landscapeCover->add( 'borderColor', new CFString( isset( $hpub_phone['landscape']['BorderColor'] ) ? $hpub_phone['landscape']['BorderColor'] : '#979797'  ) );
    $landscapeCover->add( 'borderSize', new CFNumber( 0 ) );
    $landscapeCover->add( 'shadowOpacity', new CFNumber( 0 ) );

    $phoneIssueLandscape->add( 'buttonAlign', new CFString( isset( $hpub_phone['landscape']['buttonAlign'] ) ? $hpub_phone['landscape']['buttonAlign'] : 'left'  ) );

    $phoneIssueLandscape->add( 'buttonMargin', $phoneIssueLandscapeMargin = new CFDictionary() );
    $phoneIssueLandscapeMargin->add( 'top', new CFNumber( 10 ) );
    $phoneIssueLandscapeMargin->add( 'right', new CFNumber( 10 ) );
    $phoneIssueLandscapeMargin->add( 'bottom', new CFNumber( 20 ) );
    $phoneIssueLandscapeMargin->add( 'left', new CFNumber( 10 ) );

    $phoneIssueLandscape->add( 'priceColor', new CFString( isset( $hpub_phone['landscape']['priceColor'] ) ? $hpub_phone['landscape']['priceColor'] : '#8c8c8c'  ) );
    $phoneIssueLandscape->add( 'loadingLabelColor', new CFString( isset( $hpub_phone['landscape']['loadingLabelColor'] ) ? $hpub_phone['landscape']['loadingLabelColor'] : '#97724a'  ) );
    $phoneIssueLandscape->add( 'loadingLabelFont', new CFString( 'Lato-Regular' ) );
    $phoneIssueLandscape->add( 'loadingLabelFontSize', new CFNumber( isset( $hpub_phone['landscape']['loadingLabelFontSize'] ) ? $hpub_phone['landscape']['loadingLabelFontSize'] : 11  ) );
    $phoneIssueLandscape->add( 'loadingSpinnerColor', new CFString( isset( $hpub_phone['landscape']['loadingSpinnerColor'] ) ? $hpub_phone['landscape']['loadingSpinnerColor'] : '#929292'  ) );
    $phoneIssueLandscape->add( 'progressBarTintColor', new CFString( isset( $hpub_phone['landscape']['progressBarTintColor'] ) ? $hpub_phone['landscape']['progressBarTintColor'] : '#97724a'  ) );
    $phoneIssueLandscape->add( 'progressBarBackgroundColor', new CFString( isset( $hpub_phone['landscape']['progressBarBackgroundColor'] ) ? $hpub_phone['landscape']['progressBarBackgroundColor'] : '#DDDDDD'  ) );

    $phone->add( 'navigationBarOptions', $phonenavigationBarOptions = new CFDictionary() );

    $phonenavigationBarOptions->add( 'book', $phoneNavBook = new CFDictionary() );
    $phoneNavBook->add( 'tintColor', new CFString( isset( $hpub_phone['navigationBarOptionBook']['tintColor'] ) ? $hpub_phone['navigationBarOptionBook']['tintColor'] : '#A99870'  ) );
    $phoneNavBook->add( 'titleFontSize', new CFNumber( isset( $hpub_phone['navigationBarOptionBook']['titleFontSize'] ) ? $hpub_phone['navigationBarOptionBook']['titleFontSize'] : 16  ) );
    $phoneNavBook->add( 'titleFont', new CFString( 'Gotham-Book' ) );
    $phoneNavBook->add( 'titleColor', new CFString( isset( $hpub_phone['navigationBarOptionBook']['titleColor'] ) ? $hpub_phone['navigationBarOptionBook']['titleColor'] : '#A99870'  ) );
    $phoneNavBook->add( 'backgroundColor', new CFString( isset( $hpub_phone['navigationBarOptionBook']['backgroundColor'] ) ? $hpub_phone['navigationBarOptionBook']['backgroundColor'] : '#FFFFFF'  ) );
    $phoneNavBook->add( 'marginBottom', new CFNumber( 0 ) );

    $phonenavigationBarOptions->add( 'shelf', $phoneNavShelf = new CFDictionary() );
    $phoneNavShelf->add( 'tintColor', new CFString( isset( $hpub_phone['navigationBarOptionShelf']['tintColor'] ) ? $hpub_phone['navigationBarOptionShelf']['tintColor'] : '#FFFFFF'  ) );
    $phoneNavShelf->add( 'titleFontSize', new CFNumber( isset( $hpub_phone['navigationBarOptionShelf']['titleFontSize'] ) ? $hpub_phone['navigationBarOptionShelf']['titleFontSize'] : 16  ) );
    $phoneNavShelf->add( 'titleFont', new CFString( 'Gotham-Book' ) );
    $phoneNavShelf->add( 'titleColor', new CFString( isset( $hpub_phone['navigationBarOptionShelf']['titleColor'] ) ? $hpub_phone['navigationBarOptionShelf']['titleColor'] : '#FFFFFF'  ) );
    $phoneNavShelf->add( 'backgroundColor', new CFString( isset( $hpub_phone['navigationBarOptionShelf']['backgroundColor'] ) ? $hpub_phone['navigationBarOptionShelf']['backgroundColor'] : 'clear'  ) );
    $phoneNavShelf->add( 'marginBottom', new CFNumber( 30 ) );

    $phone->add( 'subscriptionViewOptions', $phoneSubscriptionViewOptions = new CFDictionary() );
    $phoneSubscriptionViewOptions->add( 'useNativeControl', new CFBoolean( false ) );
    $phoneSubscriptionViewOptions->add( 'backgroundColor', new CFString( isset( $hpub_phone['subscriptionViewOption']['backgroundColor'] ) ? $hpub_phone['subscriptionViewOption']['backgroundColor'] : '#FFFFFF'  ) );
    $phoneSubscriptionViewOptions->add( 'buttonWidth', new CFNumber( 0 ) );
    $phoneSubscriptionViewOptions->add( 'buttonHeight', new CFNumber( 40 ) );
    $phoneSubscriptionViewOptions->add( 'buttonTintColor', new CFString( isset( $hpub_phone['subscriptionViewOption']['buttonTintColor'] ) ? $hpub_phone['subscriptionViewOption']['buttonTintColor'] : '#999999'  ) );
    $phoneSubscriptionViewOptions->add( 'buttonTintColorHighlighted', new CFString( isset( $hpub_phone['subscriptionViewOption']['buttonTintColorHighlighted'] ) ? $hpub_phone['subscriptionViewOption']['buttonTintColorHighlighted'] : '#A99870'  ) );
    $phoneSubscriptionViewOptions->add( 'buttonBackgroundColor', new CFString( isset( $hpub_phone['subscriptionViewOption']['buttonBackgroundColor'] ) ? $hpub_phone['subscriptionViewOption']['buttonBackgroundColor'] : '#FFFFFF'  ) );

    $phoneSubscriptionViewOptions->add( 'margin', $phoneSubscriptionViewOptionsMargin = new CFDictionary() );
    $phoneSubscriptionViewOptionsMargin->add( 'top', new CFNumber( 10 ) );
    $phoneSubscriptionViewOptionsMargin->add( 'right', new CFNumber( 30 ) );
    $phoneSubscriptionViewOptionsMargin->add( 'bottom', new CFNumber( 10 ) );
    $phoneSubscriptionViewOptionsMargin->add( 'left', new CFNumber( 30 ) );

    $phoneSubscriptionViewOptions->add( 'buttonFont', new CFString( 'Lato-Light' ) );
    $phoneSubscriptionViewOptions->add( 'buttonFontSize', new CFNumber( isset( $hpub_phone['subscriptionViewOption']['buttonFontSize'] ) ? $hpub_phone['subscriptionViewOption']['buttonFontSize'] : 17  ) );
    $phoneSubscriptionViewOptions->add( 'separatorColor', new CFString( isset( $hpub_phone['subscriptionViewOption']['SeparatorColor'] ) ? $hpub_phone['subscriptionViewOption']['SeparatorColor'] : '#D8D8D8'  ) );
    $phoneSubscriptionViewOptions->add( 'separatorHeight', new CFNumber( 1 ) );

    $phone->add( 'authenticationViewOptions', $phoneAuthenticationViewOptions = new CFDictionary() );
    $phoneAuthenticationViewOptions->add( 'backgroundColor', new CFString( isset( $hpub_phone['authenticationViewOptions']['backgroundColor'] ) ? $hpub_phone['authenticationViewOptions']['backgroundColor'] : '#FFFFFF'  ) );
    $phoneAuthenticationViewOptions->add( 'fieldWidth', new CFNumber( 0 ) );
    $phoneAuthenticationViewOptions->add( 'fieldHeight', new CFNumber( 40 ) );
    $phoneAuthenticationViewOptions->add( 'fieldTextColor', new CFString( isset( $hpub_phone['authenticationViewOptions']['fieldTextColor'] ) ? $hpub_phone['authenticationViewOptions']['fieldTextColor'] : '#444444'  ) );
    $phoneAuthenticationViewOptions->add( 'fieldFont', new CFString( 'Cardo-Italic' ) );
    $phoneAuthenticationViewOptions->add( 'fieldFontSize', new CFNumber( isset( $hpub_phone['authenticationViewOptions']['fieldFontSize'] ) ? $hpub_phone['authenticationViewOptions']['fieldFontSize'] : 23  ) );

    $phoneAuthenticationViewOptions->add( 'fieldMargin', $phoneAuthenticationViewOptionsMargin = new CFDictionary() );
    $phoneAuthenticationViewOptionsMargin->add( 'top', new CFNumber( 10 ) );
    $phoneAuthenticationViewOptionsMargin->add( 'right', new CFNumber( 30 ) );
    $phoneAuthenticationViewOptionsMargin->add( 'bottom', new CFNumber( 10 ) );
    $phoneAuthenticationViewOptionsMargin->add( 'left', new CFNumber( 30 ) );

    $phoneAuthenticationViewOptions->add( 'fieldPlaceholderFont', new CFString( 'Cardo-Italic' ) );
    $phoneAuthenticationViewOptions->add( 'fieldPlaceholderColor', new CFString( isset( $hpub_phone['authenticationViewOptions']['fieldPlaceholderColor'] ) ? $hpub_phone['authenticationViewOptions']['fieldPlaceholderColor'] : '#999999'  ) );
    $phoneAuthenticationViewOptions->add( 'buttonFont', new CFString( 'Lato-Regular' ) );
    $phoneAuthenticationViewOptions->add( 'buttonFontSize', new CFNumber( isset( $hpub_phone['authenticationViewOptions']['buttonFontSize'] ) ? $hpub_phone['authenticationViewOptions']['buttonFontSize'] : 17  ) );
    $phoneAuthenticationViewOptions->add( 'buttonTintColor', new CFString( isset( $hpub_phone['authenticationViewOptions']['buttonTintColor'] ) ? $hpub_phone['authenticationViewOptions']['buttonTintColor'] : '#444444'  ) );
    $phoneAuthenticationViewOptions->add( 'buttonTintColorHighlighted', new CFString( isset( $hpub_phone['authenticationViewOptions']['buttonTintColorHighlighted'] ) ? $hpub_phone['authenticationViewOptions']['buttonTintColorHighlighted'] : '#A99870'  ) );
    $phoneAuthenticationViewOptions->add( 'buttonBackgroundColor', new CFString( isset( $hpub_phone['authenticationViewOptions']['buttonBackgroundColor'] ) ? $hpub_phone['authenticationViewOptions']['buttonBackgroundColor'] : '#FFFFFF'  ) );
    $phoneAuthenticationViewOptions->add( 'buttonBorderSize', new CFNumber( 1 ) );
    $phoneAuthenticationViewOptions->add( 'buttonBorderColor', new CFString( isset( $hpub_phone['authenticationViewOptions']['buttonBorderColor'] ) ? $hpub_phone['authenticationViewOptions']['buttonBorderColor'] : '#A6A6A6'  ) );
    $phoneAuthenticationViewOptions->add( 'labelTextColor', new CFString( isset( $hpub_phone['authenticationViewOptions']['labelTextColor'] ) ? $hpub_phone['authenticationViewOptions']['labelTextColor'] : '#444444'  ) );
    $phoneAuthenticationViewOptions->add( 'labelFont', new CFString( 'Lato-Regular' ) );
    $phoneAuthenticationViewOptions->add( 'labelFontSize', new CFNumber( isset( $hpub_phone['authenticationViewOptions']['labelFontSize'] ) ? $hpub_phone['authenticationViewOptions']['labelFontSize'] : 14  ) );
    $phoneAuthenticationViewOptions->add( 'separatorColor', new CFString( isset( $hpub_phone['authenticationViewOptions']['separatorColor'] ) ? $hpub_phone['authenticationViewOptions']['separatorColor'] : '#D8D8D8'  ) );
    $phoneAuthenticationViewOptions->add( 'separatorHeight', new CFNumber( 1 ) );

    /*
     * Save PList as XML
     */

    $plist->saveXML( PR_CLIENT_SETTINGS_PATH  . $eproject_slug . '.xml.plist' );

  }
  /**
   * Get subscription method for editorial project
   *
   * @param  int $term_id
   * @return array or bool
   */
    protected static function _get_subscription_method( $eproject_id ) {

      $options = get_option( 'taxonomy_term_' . $eproject_id );
      $subscription_types = $options['_pr_subscription_types'];
      $subscription_methods = $options['_pr_subscription_method'];
      $methods = array();
      if ( isset( $subscription_types ) && !empty( $subscription_types ) ) {
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
