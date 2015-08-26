<?php
/**
 *
 * Create the PropertyList pressroom.plist by using the CFPropertyList API.
 * @package plist
 * @subpackage plist.examples
 */

namespace CFPropertyList;
use PR_Editorial_Project, PR_Utils;

class pressroom_Plist {

  public function __construct() {

    add_action( 'edited_' . PR_EDITORIAL_PROJECT, array( $this, 'action_get_settings' ), 50 );
    add_action( 'create_' . PR_EDITORIAL_PROJECT, array( $this, 'action_get_settings' ), 50 );
  }

  public function action_get_settings( $eproject_id ) {

    $eproject = get_term( $eproject_id, PR_EDITORIAL_PROJECT );
    $eproject_slug = $eproject->slug;

    $plist = new CFPropertyList();

    $plist->add( $dict = new CFDictionary() );

    $dict->add( 'isNewsstand', new CFBoolean( true ) );
    $dict->add( 'newsstandLatestIssueCover', new CFBoolean( true ) );
    $dict->add( 'newsstandManifestUrl', new CFString( site_url() . "/pressroom-api/shelf/{$eproject_slug}" ) );
    $dict->add( 'purchaseConfirmationUrl', new CFString( site_url() . "/pressroom-api/purchaseConfirmationUrl/:app_id/:user_id/{$eproject_slug}" ) );
    $dict->add( 'checkoutUrl', new CFString( site_url() . "/pressroom_checkout/:app_id/:user_id/{$eproject_slug}" ) );
    $dict->add( 'purchasesUrl', new CFString( site_url() . "/pressroom-api/itunes_purchases_list/:app_id/:user_id/{$eproject_slug}" ) );
    $dict->add( 'postApnsTokenUrl', new CFString( site_url() . "/pressroom-api/apns_token/:app_id/:user_id/{$eproject_slug}" ) );
    $dict->add( 'authenticationUrl', new CFString( site_url() . "/pressroom-api/authentication/:app_id/:user_id/{$eproject_slug}" ) );
    $dict->add( 'autoRenewableSubscriptionProductIds', $productIds = new CFArray() );

    $subscriptions = PR_Editorial_Project::get_subscriptions_id( $eproject->term_id );
    foreach( $subscriptions as $sub ) {
      $productIds->add( new CFString( $sub ) );
    }
    $free_subscription_id = PR_Editorial_Project::get_free_subscription_id( $eproject->term_id );
    $dict->add( 'freeSubscriptionProductId', new CFString( $free_subscription_id ? $free_subscription_id : "" ));
    $dict->add( 'requestTimeout', new CFNumber( 15 ) );

    /* Resource Bundle */
    $dict->add( 'resourceBundleName', new CFString( "{$eproject_slug}.images" ) );
    $dict->add( 'resourceBundleUrl', new CFString( PR_IOS_SETTINGS_URI . $eproject_slug . '.images.zip' ) );

    /* Pad */
    $dict->add( 'Pad', $pad = new CFDictionary() );

    /* Pad Issue Shelf Shelf */
    $eproject_sgs = get_option( 'taxonomy_term_' . $eproject_id );

    $pad->add( 'shelf', $padShelf = new CFDictionary() );
    $padShelf->add( 'layoutType', new CFString( 'grid' ) );
    $padShelf->add( 'gridColumns', new CFNumber( 2 ) );
    $padShelf->add( 'scrollDirection', new CFString( 'vertical' ) );
    $padShelf->add( 'pagingEnabled', new CFBoolean( false ) );
    $padShelf->add( 'backgroundFillStyle', new CFString( isset( $eproject_sgs['_pr_pad_sgs_shelf_backgroundFillStyle'] ) ? $eproject_sgs['_pr_pad_sgs_shelf_backgroundFillStyle'] : 'Image' ) );
    $padShelf->add( 'backgroundFillGradientStart', new CFString( isset( $eproject_sgs['_pr_pad_sgs_shelf_backgroundFillGradientStart'] ) ? $eproject_sgs['_pr_pad_sgs_shelf_backgroundFillGradientStart'] : '#FFFFFF' ) );
    $padShelf->add( 'backgroundFillGradientStop', new CFString( isset( $eproject_sgs['_pr_pad_sgs_shelf_backgroundFillGradientStop'] ) ? $eproject_sgs['_pr_pad_sgs_shelf_backgroundFillGradientStop'] : '#EEEEEE' ) );
    $padShelf->add( 'backgroundFillColor', new CFString( isset( $eproject_sgs['_pr_pad_sgs_shelf_backgroundFillStyleColor'] ) ? $eproject_sgs['_pr_pad_sgs_shelf_backgroundFillStyleColor'] : '#FFFFFF') );
    $padShelf->add( 'backgroundFitStyle', new CFBoolean( isset( $eproject_sgs['_pr_pad_sgs_shelf_backgroundFitStyle'] ) ? $eproject_sgs['_pr_pad_sgs_shelf_backgroundFitStyle'] : false ) );

    $padShelf->add( 'headerHidden', new CFBoolean( isset( $eproject_sgs['_pr_pad_sgs_shelf_headerHidden'] ) ? $eproject_sgs['_pr_pad_sgs_shelf_headerHidden'] : false  ) );
    $padShelf->add( 'headerSticky', new CFBoolean( isset( $eproject_sgs['_pr_pad_sgs_shelf_headerSticky'] ) ? $eproject_sgs['_pr_pad_sgs_shelf_headerSticky'] : false ) );
    $padShelf->add( 'headerStretch', new CFBoolean( isset( $eproject_sgs['_pr_pad_sgs_shelf_headerStretch'] ) ? $eproject_sgs['_pr_pad_sgs_shelf_headerStretch'] : false ) );
    $padShelf->add( 'headerBackgroundColor', new CFString( isset( $eproject_sgs['_pr_pad_sgs_shelf_headerBackgroundColor'] ) ? $eproject_sgs['_pr_pad_sgs_shelf_headerBackgroundColor'] : 'clear' ) );
    $padShelf->add( 'headerImageFill', new CFBoolean( isset( $eproject_sgs['_pr_pad_sgs_shelf_headerImageFill'] ) ? $eproject_sgs['_pr_pad_sgs_shelf_headerImageFill'] : false ) );
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
    $portraitTitleLabel->add( 'align', new CFString( isset( $eproject_sgs['_pr_pad_sgs_portrait_titleLabelAlign'] ) ? $eproject_sgs['_pr_pad_sgs_portrait_titleLabelAlign'] : 'left'  ) );
    $portraitTitleLabel->add( 'font', new CFString( 'Gotham-Book' ) );
    $portraitTitleLabel->add( 'fontSize', new CFNumber( isset( $eproject_sgs['_pr_pad_sgs_portrait_titleLabelFontSize'] ) ? $eproject_sgs['_pr_pad_sgs_portrait_titleLabelFontSize'] : 14 ) );
    $portraitTitleLabel->add( 'color', new CFString( isset( $eproject_sgs['_pr_pad_sgs_portrait_titleLabelColor'] ) ? $eproject_sgs['_pr_pad_sgs_portrait_titleLabelColor'] : '#A87F52' ) );

    $portraitTitleLabel->add( 'margin', $portraitTitleLabelMargin = new CFDictionary() );
    $portraitTitleLabelMargin->add( 'top', new CFNumber( 0 ) );
    $portraitTitleLabelMargin->add( 'right', new CFNumber( 0 ) );
    $portraitTitleLabelMargin->add( 'bottom', new CFNumber( 0 ) );
    $portraitTitleLabelMargin->add( 'left', new CFNumber( 15 ) );

    $padIssuePortrait->add( 'infoLabel', $portraitInfoLabel = new CFDictionary() );
    $portraitInfoLabel->add( 'align', new CFString( isset( $eproject_sgs['_pr_pad_sgs_portrait_infoLabelAlign'] ) ? $eproject_sgs['_pr_pad_sgs_portrait_infoLabelAlign'] : 'left'  ) );
    $portraitInfoLabel->add( 'font', new CFString( 'Gotham-Book' ) );
    $portraitInfoLabel->add( 'fontSize', new CFNumber( isset( $eproject_sgs['_pr_pad_sgs_portrait_infoLabelFontSize'] ) ? $eproject_sgs['_pr_pad_sgs_portrait_infoLabelFontSize'] : 13 ) );
    $portraitInfoLabel->add( 'color', new CFString( isset( $eproject_sgs['_pr_pad_sgs_portrait_infoLabelColor'] ) ? $eproject_sgs['_pr_pad_sgs_portrait_infoLabelColor'] : '#8C8C8C' ) );
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
    $portraitActionButton->add( 'fontSize', new CFNumber( isset( $eproject_sgs['_pr_pad_sgs_portrait_actionButtonFontSize'] ) ? $eproject_sgs['_pr_pad_sgs_portrait_actionButtonFontSize'] : 13 ) );
    $portraitActionButton->add( 'backgroundColor', new CFString( isset( $eproject_sgs['_pr_pad_sgs_portrait_actionButtonBackgroundColor'] ) ? $eproject_sgs['_pr_pad_sgs_portrait_actionButtonBackgroundColor'] : '#97724A'  ) );
    $portraitActionButton->add( 'textColor', new CFString( isset( $eproject_sgs['_pr_pad_sgs_portrait_actionButtonTextColor'] ) ? $eproject_sgs['_pr_pad_sgs_portrait_actionButtonTextColor'] : '#FFFFFF'  ) );

    $padIssuePortrait->add( 'archiveButton', $portraitArchiveButton = new CFDictionary() );
    $portraitArchiveButton->add( 'width', new CFNumber( 30 ) );
    $portraitArchiveButton->add( 'height', new CFNumber( 30 ) );
    $portraitArchiveButton->add( 'font', new CFString( 'Gotham-Book' ) );
    $portraitArchiveButton->add( 'fontSize', new CFNumber( isset( $eproject_sgs['_pr_pad_sgs_portrait_archiveButtonFontSize'] ) ? $eproject_sgs['_pr_pad_sgs_portrait_archiveButtonFontSize'] : 14  ) );
    $portraitArchiveButton->add( 'backgroundColor', new CFString( isset( $eproject_sgs['_pr_pad_sgs_portrait_archiveButtonBackgroundColor'] ) ? $eproject_sgs['_pr_pad_sgs_portrait_archiveButtonBackgroundColor'] : '#A8A8A8'  ) );
    $portraitArchiveButton->add( 'textColor', new CFString( isset( $eproject_sgs['_pr_pad_sgs_portrait_archiveButtonTextColor'] ) ? $eproject_sgs['_pr_pad_sgs_portrait_archiveButtonTextColor'] : '#FFFFFF'  ) );

    $padIssuePortrait->add( 'cover', $portraitCover = new CFDictionary() );
    $portraitCover->add( 'backgroundColor', new CFString( isset( $eproject_sgs['_pr_pad_sgs_portrait_coverBackgroundColor'] ) ? $eproject_sgs['_pr_pad_sgs_portrait_coverBackgroundColor'] : '#FFFFFF'  ) );
    $portraitCover->add( 'borderColor', new CFString( isset( $eproject_sgs['_pr_pad_sgs_portrait_borderColor'] ) ? $eproject_sgs['_pr_pad_sgs_portrait_borderColor'] : '#979797'  ) );
    $portraitCover->add( 'borderSize', new CFNumber( 0 ) );
    $portraitCover->add( 'shadowOpacity', new CFNumber( 0 ) );

    $padIssuePortrait->add( 'buttonAlign', new CFString( isset( $eproject_sgs['_pr_pad_sgs_portrait_buttonAlign'] ) ? $eproject_sgs['_pr_pad_sgs_portrait_buttonAlign'] : 'left'  ) );

    $padIssuePortrait->add( 'buttonMargin', $padIssuePortraitMargin = new CFDictionary() );
    $padIssuePortraitMargin->add( 'top', new CFNumber( 0 ) );
    $padIssuePortraitMargin->add( 'right', new CFNumber( 0 ) );
    $padIssuePortraitMargin->add( 'bottom', new CFNumber( 0 ) );
    $padIssuePortraitMargin->add( 'left', new CFNumber( 15 ) );

    $padIssuePortrait->add( 'priceColor', new CFString( isset( $eproject_sgs['_pr_pad_sgs_portrait_priceColor'] ) ? $eproject_sgs['_pr_pad_sgs_portrait_priceColor'] : '#FFFFFF'  ) );
    $padIssuePortrait->add( 'loadingLabelColor', new CFString( isset( $eproject_sgs['_pr_pad_sgs_portrait_loadingLabelColor'] ) ? $eproject_sgs['_pr_pad_sgs_portrait_loadingLabelColor'] : '#A87F52'  ) );
    $padIssuePortrait->add( 'loadingLabelFont', new CFString( 'Gotham-Book' ) );
    $padIssuePortrait->add( 'loadingLabelFontSize', new CFNumber( isset( $eproject_sgs['_pr_pad_sgs_portrait_loadingLabelFontSize'] ) ? $eproject_sgs['_pr_pad_sgs_portrait_loadingLabelFontSize'] : 11  ) );
    $padIssuePortrait->add( 'loadingSpinnerColor', new CFString( isset( $eproject_sgs['_pr_pad_sgs_portrait_loadingSpinnerColor'] ) ? $eproject_sgs['_pr_pad_sgs_portrait_loadingSpinnerColor'] : '#A87F52'  ) );
    $padIssuePortrait->add( 'progressBarTintColor', new CFString( isset( $eproject_sgs['_pr_pad_sgs_portrait_progressBarTintColor'] ) ? $eproject_sgs['_pr_pad_sgs_portrait_progressBarTintColor'] : '#A87F52'  ) );
    $padIssuePortrait->add( 'progressBarBackgroundColor', new CFString( isset( $eproject_sgs['_pr_pad_sgs_portrait_progressBarBackgroundColor'] ) ? $eproject_sgs['_pr_pad_sgs_portrait_progressBarBackgroundColor'] : '#DDDDDD'  ) );

    /* Landscape */
    $padIssue->add( 'landscape', $padIssueLandscape = new CFDictionary() );
    $padIssueLandscape->add( 'cellHeight', new CFNumber( 230 ) );

    $padIssueLandscape->add( 'cellPadding', $landscapeCellpadding = new CFDictionary() );
    $landscapeCellpadding->add( 'top', new CFNumber( 50 ) );
    $landscapeCellpadding->add( 'right', new CFNumber( 25 ) );
    $landscapeCellpadding->add( 'bottom', new CFNumber( 0 ) );
    $landscapeCellpadding->add( 'left', new CFNumber( 25 ) );

    $padIssueLandscape->add( 'titleLabel', $landscapeTitleLabel = new CFDictionary() );
    $landscapeTitleLabel->add( 'align', new CFString( isset( $eproject_sgs['_pr_pad_sgs_landscape_titleLabelAlign'] ) ? $eproject_sgs['_pr_pad_sgs_landscape_titleLabelAlign'] : 'left'  ) );
    $landscapeTitleLabel->add( 'font', new CFString( 'Gotham-Book' ) );
    $landscapeTitleLabel->add( 'fontSize', new CFNumber( isset( $eproject_sgs['_pr_pad_sgs_landscape_titleLabelFontSize'] ) ? $eproject_sgs['_pr_pad_sgs_landscape_titleLabelFontSize'] : 14 ) );
    $landscapeTitleLabel->add( 'color', new CFString( isset( $eproject_sgs['_pr_pad_sgs_landscape_titleLabelColor'] ) ? $eproject_sgs['_pr_pad_sgs_landscape_titleLabelColor'] : '#A87F52' ) );

    $landscapeTitleLabel->add( 'margin', $landscapeTitleLabelMargin = new CFDictionary() );
    $landscapeTitleLabelMargin->add( 'top', new CFNumber( 0 ) );
    $landscapeTitleLabelMargin->add( 'right', new CFNumber( 0 ) );
    $landscapeTitleLabelMargin->add( 'bottom', new CFNumber( 0 ) );
    $landscapeTitleLabelMargin->add( 'left', new CFNumber( 15 ) );

    $padIssueLandscape->add( 'infoLabel', $landscapeInfoLabel = new CFDictionary() );
    $landscapeInfoLabel->add( 'align', new CFString( isset( $eproject_sgs['_pr_pad_sgs_landscape_infoLabelAlign'] ) ? $eproject_sgs['_pr_pad_sgs_landscape_infoLabelAlign'] : 'left'  ) );
    $landscapeInfoLabel->add( 'font', new CFString( 'Gotham-Book' ) );
    $landscapeInfoLabel->add( 'fontSize', new CFNumber( isset( $eproject_sgs['_pr_pad_sgs_landscape_infoLabelFontSize'] ) ? $eproject_sgs['_pr_pad_sgs_landscape_infoLabelFontSize'] : 13 ) );
    $landscapeInfoLabel->add( 'color', new CFString( isset( $eproject_sgs['_pr_pad_sgs_landscape_infoLabelColor'] ) ? $eproject_sgs['_pr_pad_sgs_landscape_infoLabelColor'] : '#8C8C8C' ) );
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
    $landscapeActionButton->add( 'fontSize', new CFNumber( isset( $eproject_sgs['_pr_pad_sgs_landscape_actionButtonFontSize'] ) ? $eproject_sgs['_pr_pad_sgs_landscape_actionButtonFontSize'] : 14  ) );
    $landscapeActionButton->add( 'backgroundColor', new CFString( isset( $eproject_sgs['_pr_pad_sgs_landscape_actionButtonBackgroundColor'] ) ? $eproject_sgs['_pr_pad_sgs_landscape_actionButtonBackgroundColor'] : '#97724A'  ) );
    $landscapeActionButton->add( 'textColor', new CFString( isset( $eproject_sgs['_pr_pad_sgs_landscape_actionButtonTextColor'] ) ? $eproject_sgs['_pr_pad_sgs_landscape_actionButtonTextColor'] : '#FFFFFF'  ) );

    $padIssueLandscape->add( 'archiveButton', $landscapeArchiveButton = new CFDictionary() );
    $landscapeArchiveButton->add( 'width', new CFNumber( 30 ) );
    $landscapeArchiveButton->add( 'height', new CFNumber( 30 ) );
    $landscapeArchiveButton->add( 'font', new CFString( 'Gotham-Book' ) );
    $landscapeArchiveButton->add( 'fontSize', new CFNumber( isset( $eproject_sgs['_pr_pad_sgs_landscape_archiveButtonFontSize'] ) ? $eproject_sgs['_pr_pad_sgs_landscape_archiveButtonFontSize'] : 14 ) );
    $landscapeArchiveButton->add( 'backgroundColor', new CFString( isset( $eproject_sgs['_pr_pad_sgs_landscape_archiveButtonBackgroundColor'] ) ? $eproject_sgs['_pr_pad_sgs_landscape_archiveButtonBackgroundColor'] : '#A8A8A8'  ) );
    $landscapeArchiveButton->add( 'textColor', new CFString( isset( $eproject_sgs['_pr_pad_sgs_landscape_archiveButtonTextColor'] ) ? $eproject_sgs['_pr_pad_sgs_landscape_archiveButtonTextColor'] : '#FFFFFF'  ) );

    $padIssueLandscape->add( 'cover', $landscapeCover = new CFDictionary() );
    $landscapeCover->add( 'backgroundColor', new CFString( isset( $eproject_sgs['_pr_pad_sgs_landscape_coverBackgroundColor'] ) ? $eproject_sgs['_pr_pad_sgs_landscape_coverBackgroundColor'] : '#FFFFFF'  ) );
    $landscapeCover->add( 'borderColor', new CFString( isset( $eproject_sgs['_pr_pad_sgs_landscape_borderColor'] ) ? $eproject_sgs['_pr_pad_sgs_landscape_borderColor'] : '#979797'  ) );
    $landscapeCover->add( 'borderSize', new CFNumber( 0 ) );
    $landscapeCover->add( 'shadowOpacity', new CFNumber( 0 ) );

    $padIssueLandscape->add( 'buttonAlign', new CFString( isset( $eproject_sgs['_pr_pad_sgs_landscape_buttonAlign'] ) ? $eproject_sgs['_pr_pad_sgs_landscape_buttonAlign'] : 'left'  ) );

    $padIssueLandscape->add( 'buttonMargin', $padIssueLandscapeMargin = new CFDictionary() );
    $padIssueLandscapeMargin->add( 'top', new CFNumber( 0 ) );
    $padIssueLandscapeMargin->add( 'right', new CFNumber( 0 ) );
    $padIssueLandscapeMargin->add( 'bottom', new CFNumber( 0 ) );
    $padIssueLandscapeMargin->add( 'left', new CFNumber( 15 ) );

    $padIssueLandscape->add( 'priceColor', new CFString( isset( $eproject_sgs['_pr_pad_sgs_landscape_priceColor'] ) ? $eproject_sgs['_pr_pad_sgs_landscape_priceColor'] : '#FFFFFF'  ) );
    $padIssueLandscape->add( 'loadingLabelColor', new CFString( isset( $eproject_sgs['_pr_pad_sgs_landscape_loadingLabelColor'] ) ? $eproject_sgs['_pr_pad_sgs_landscape_loadingLabelColor'] : '#A87F52'  ) );
    $padIssueLandscape->add( 'loadingLabelFont', new CFString( 'Gotham-Book' ) );
    $padIssueLandscape->add( 'loadingLabelFontSize', new CFNumber( isset( $eproject_sgs['_pr_pad_sgs_landscape_loadingLabelFontSize'] ) ? $eproject_sgs['_pr_pad_sgs_landscape_loadingLabelFontSize'] : 11  ) );
    $padIssueLandscape->add( 'loadingSpinnerColor', new CFString( isset( $eproject_sgs['_pr_pad_sgs_landscape_loadingSpinnerColor'] ) ? $eproject_sgs['_pr_pad_sgs_landscape_loadingSpinnerColor'] : '#A87F52'  ) );
    $padIssueLandscape->add( 'progressBarTintColor', new CFString( isset( $eproject_sgs['_pr_pad_sgs_landscape_progressBarTintColor'] ) ? $eproject_sgs['_pr_pad_sgs_landscape_progressBarTintColor'] : '#A87F52'  ) );
    $padIssueLandscape->add( 'progressBarBackgroundColor', new CFString( isset( $eproject_sgs['_pr_pad_sgs_landscape_progressBarBackgroundColor'] ) ? $eproject_sgs['_pr_pad_sgs_landscape_progressBarBackgroundColor'] : '#DDDDDD'  ) );

    $pad->add( 'navigationBarOptions', $padNavBar = new CFDictionary() );

    $padNavBar->add( 'book', $padNavBook = new CFDictionary() );
    $padNavBook->add( 'tintColor', new CFString( isset( $eproject_sgs['_pr_pad_sgs_navBarBook_tintColor'] ) ? $eproject_sgs['_pr_pad_sgs_navBarBook_tintColor'] : '#97724A'  ) );
    $padNavBook->add( 'titleFontSize', new CFNumber( isset( $eproject_sgs['_pr_pad_sgs_navBarBook_titleFontSize'] ) ? $eproject_sgs['_pr_pad_sgs_navBarBook_titleFontSize'] : 18  ) );
    $padNavBook->add( 'titleFont', new CFString( 'Gotham-Book' ) );
    $padNavBook->add( 'titleColor', new CFString( isset( $eproject_sgs['_pr_pad_sgs_navBarBook_titleColor'] ) ? $eproject_sgs['_pr_pad_sgs_navBarBook_titleColor'] : '#97724A'  ) );
    $padNavBook->add( 'backgroundColor', new CFString( isset( $eproject_sgs['_pr_pad_sgs_navBarBook_backgroundColor'] ) ? $eproject_sgs['_pr_pad_sgs_navBarBook_backgroundColor'] : '#FFFFFF'  ) );
    $padNavBook->add( 'marginBottom', new CFNumber( 60 ) );

    $padNavBar->add( 'shelf', $padNavShelf = new CFDictionary() );
    $padNavShelf->add( 'tintColor', new CFString( isset( $eproject_sgs['_pr_pad_sgs_navBarShelf_tintColor'] ) ? $eproject_sgs['_pr_pad_sgs_navBarShelf_tintColor'] : '#A99870'  ) );
    $padNavShelf->add( 'titleFontSize', new CFNumber( isset( $eproject_sgs['_pr_pad_sgs_navBarShelf_titleFontSize'] ) ? $eproject_sgs['_pr_pad_sgs_navBarShelf_titleFontSize'] : 16  ) );
    $padNavShelf->add( 'titleFont', new CFString( 'Gotham-Book' ) );
    $padNavShelf->add( 'titleColor', new CFString( isset( $eproject_sgs['_pr_pad_sgs_navBarShelf_titleColor'] ) ? $eproject_sgs['_pr_pad_sgs_navBarShelf_titleColor'] : '#A99870'  ) );
    $padNavShelf->add( 'backgroundColor', new CFString( isset( $eproject_sgs['_pr_pad_sgs_navBarShelf_backgroundColor'] ) ? $eproject_sgs['_pr_pad_sgs_navBarShelf_backgroundColor'] : '#FFFFFF'  ) );
    $padNavShelf->add( 'marginBottom', new CFNumber( 60 ) );

    $pad->add( 'subscriptionViewOptions', $padSubscriptionViewOptions = new CFDictionary() );
    $padSubscriptionViewOptions->add( 'useNativeControl', new CFBoolean( false ) );
    $padSubscriptionViewOptions->add( 'backgroundColor', new CFString( isset( $eproject_sgs['_pr_pad_sgs_subView_backgroundColor'] ) ? $eproject_sgs['_pr_pad_sgs_subView_backgroundColor'] : '#FFFFFF'  ) );
    $padSubscriptionViewOptions->add( 'buttonWidth', new CFNumber( 0 ) );
    $padSubscriptionViewOptions->add( 'buttonHeight', new CFNumber( 60 ) );
    $padSubscriptionViewOptions->add( 'buttonTintColor', new CFString( isset( $eproject_sgs['_pr_pad_sgs_subView_buttonTintColor'] ) ? $eproject_sgs['_pr_pad_sgs_subView_buttonTintColor'] : '#999999'  ) );
    $padSubscriptionViewOptions->add( 'buttonTintColorHighlighted', new CFString( isset( $eproject_sgs['_pr_pad_sgs_subView_buttonTintColorHighlighted'] ) ? $eproject_sgs['_pr_pad_sgs_subView_buttonTintColorHighlighted'] : '#A99870'  ) );
    $padSubscriptionViewOptions->add( 'buttonBackgroundColor', new CFString( isset( $eproject_sgs['_pr_pad_sgs_subView_buttonBackgroundColor'] ) ? $eproject_sgs['_pr_pad_sgs_subView_buttonBackgroundColor'] : '#FFFFFF'  ) );

    $padSubscriptionViewOptions->add( 'margin', $padSubscriptionViewOptionsMargin = new CFDictionary() );
    $padSubscriptionViewOptionsMargin->add( 'top', new CFNumber( 10 ) );
    $padSubscriptionViewOptionsMargin->add( 'right', new CFNumber( 30 ) );
    $padSubscriptionViewOptionsMargin->add( 'bottom', new CFNumber( 10 ) );
    $padSubscriptionViewOptionsMargin->add( 'left', new CFNumber( 30 ) );

    $padSubscriptionViewOptions->add( 'buttonFont', new CFString( 'Lato-Regular' ) );
    $padSubscriptionViewOptions->add( 'buttonFontSize', new CFNumber( isset( $eproject_sgs['_pr_pad_sgs_subView_buttonFontSize'] ) ? $eproject_sgs['_pr_pad_sgs_subView_buttonFontSize'] : 21  ) );
    $padSubscriptionViewOptions->add( 'separatorColor', new CFString( isset( $eproject_sgs['_pr_pad_sgs_subView_separatorColor'] ) ? $eproject_sgs['_pr_pad_sgs_subView_separatorColor'] : '#D8D8D8'  ) );
    $padSubscriptionViewOptions->add( 'separatorHeight', new CFNumber( 1 ) );

    $pad->add( 'authenticationViewOptions', $padAuthenticationViewOptions = new CFDictionary() );
    $padAuthenticationViewOptions->add( 'backgroundColor', new CFString( isset( $eproject_sgs['_pr_pad_sgs_authView_backgroundColor'] ) ? $eproject_sgs['_pr_pad_sgs_authView_backgroundColor'] : '#FFFFFF'  ) );
    $padAuthenticationViewOptions->add( 'fieldWidth', new CFNumber( 0 ) );
    $padAuthenticationViewOptions->add( 'fieldHeight', new CFNumber( 40 ) );
    $padAuthenticationViewOptions->add( 'fieldTextColor', new CFString( isset( $eproject_sgs['_pr_pad_sgs_authView_fieldTextColor'] ) ? $eproject_sgs['_pr_pad_sgs_authView_fieldTextColor'] : '#444444'  ) );
    $padAuthenticationViewOptions->add( 'fieldFont', new CFString( 'Cardo-Italic' ) );
    $padAuthenticationViewOptions->add( 'fieldFontSize', new CFNumber( isset( $eproject_sgs['_pr_pad_sgs_authView_fieldFontSize'] ) ? $eproject_sgs['_pr_pad_sgs_authView_fieldFontSize'] : 23 ) );

    $padAuthenticationViewOptions->add( 'fieldMargin', $padAuthenticationViewOptionsMargin = new CFDictionary() );
    $padAuthenticationViewOptionsMargin->add( 'top', new CFNumber( 10 ) );
    $padAuthenticationViewOptionsMargin->add( 'right', new CFNumber( 30 ) );
    $padAuthenticationViewOptionsMargin->add( 'bottom', new CFNumber( 10 ) );
    $padAuthenticationViewOptionsMargin->add( 'left', new CFNumber( 30 ) );

    $padAuthenticationViewOptions->add( 'fieldPlaceholderFont', new CFString( 'Cardo-Italic' ) );
    $padAuthenticationViewOptions->add( 'fieldPlaceholderColor', new CFString( isset( $eproject_sgs['_pr_pad_sgs_authView_fieldPlaceholderColor'] ) ? $eproject_sgs['_pr_pad_sgs_authView_fieldPlaceholderColor'] : '#999999'  ) );
    $padAuthenticationViewOptions->add( 'buttonFont', new CFString( 'Lato-Regular' ) );
    $padAuthenticationViewOptions->add( 'buttonFontSize', new CFNumber( isset( $eproject_sgs['_pr_pad_sgs_authView_buttonFontSize'] ) ? $eproject_sgs['_pr_pad_sgs_authView_buttonFontSize'] : 17 ) );
    $padAuthenticationViewOptions->add( 'buttonTintColor', new CFString( isset( $eproject_sgs['_pr_pad_sgs_authView_buttonTintColor'] ) ? $eproject_sgs['_pr_pad_sgs_authView_buttonTintColor'] : '#444444'  ) );
    $padAuthenticationViewOptions->add( 'buttonTintColorHighlighted', new CFString( isset( $eproject_sgs['_pr_pad_sgs_authView_buttonTintColorHighlighted'] ) ? $eproject_sgs['_pr_pad_sgs_authView_buttonTintColorHighlighted'] : '#A99870'  ) );
    $padAuthenticationViewOptions->add( 'buttonBackgroundColor', new CFString( isset( $eproject_sgs['_pr_pad_sgs_authView_buttonBackgroundColor'] ) ? $eproject_sgs['_pr_pad_sgs_authView_buttonBackgroundColor'] : '#FFFFFF'  ) );
    $padAuthenticationViewOptions->add( 'buttonBorderSize', new CFNumber( 1 ) );
    $padAuthenticationViewOptions->add( 'buttonBorderColor', new CFString( isset( $eproject_sgs['_pr_pad_sgs_authView_buttonBorderColor'] ) ? $eproject_sgs['_pr_pad_sgs_authView_buttonBorderColor'] : '#A6A6A6'  ) );
    $padAuthenticationViewOptions->add( 'labelTextColor', new CFString( isset( $eproject_sgs['_pr_pad_sgs_authView_labelTextColor'] ) ? $eproject_sgs['_pr_pad_sgs_authView_labelTextColor'] : '#444444'  ) );
    $padAuthenticationViewOptions->add( 'labelFont', new CFString( 'Lato-Regular' ) );
    $padAuthenticationViewOptions->add( 'labelFontSize', new CFNumber( isset( $eproject_sgs['_pr_pad_sgs_authView_labelFontSize'] ) ? $eproject_sgs['_pr_pad_sgs_authView_labelFontSize'] : 14  ) );
    $padAuthenticationViewOptions->add( 'separatorColor', new CFString( isset( $eproject_sgs['_pr_pad_sgs_authView_separatorColor'] ) ? $eproject_sgs['_pr_pad_sgs_authView_separatorColor'] : '#D8D8D8'  ) );
    $padAuthenticationViewOptions->add( 'separatorHeight', new CFNumber( 1 ) );

    /* Phone */
    $dict->add( 'Phone', $phone = new CFDictionary() );

    /* Phone Issue Shelf Shelf */
    $phone->add( 'shelf', $phoneShelf = new CFDictionary() );
    $phoneShelf->add( 'layoutType', new CFString( 'coverflow' ) );
    $phoneShelf->add( 'gridColumns', new CFNumber( 1 ) );
    $phoneShelf->add( 'scrollDirection', new CFString( 'horizontal' ) );
    $phoneShelf->add( 'pagingEnabled', new CFBoolean( true ) );
    $phoneShelf->add( 'backgroundFillStyle', new CFString( isset( $eproject_sgs['_pr_phone_sgs_shelf_backgroundFillStyle'] ) ? $eproject_sgs['_pr_phone_sgs_shelf_backgroundFillStyle'] : 'Image' ) );
    $phoneShelf->add( 'backgroundFillGradientStart', new CFString( isset( $eproject_sgs['_pr_phone_sgs_shelf_backgroundFillGradientStart'] ) ? $eproject_sgs['_pr_phone_sgs_shelf_backgroundFillGradientStart'] : '#FFFFFF' ) );
    $phoneShelf->add( 'backgroundFillGradientStop', new CFString( isset( $eproject_sgs['_pr_phone_sgs_shelf_backgroundFillGradientStop'] ) ? $eproject_sgs['_pr_phone_sgs_shelf_backgroundFillGradientStop'] : '#EEEEEE' ) );
    $phoneShelf->add( 'backgroundFillColor', new CFString( isset( $eproject_sgs['_pr_phone_sgs_shelf_backgroundFillStyleColor'] ) ? $eproject_sgs['_pr_phone_sgs_shelf_backgroundFillStyleColor'] : '#FFFFFF') );
    $phoneShelf->add( 'backgroundFitStyle', new CFBoolean( isset( $eproject_sgs['_pr_phone_sgs_shelf_backgroundFitStyle'] ) ? $eproject_sgs['_pr_phone_sgs_shelf_backgroundFitStyle'] : false ) );
    $phoneShelf->add( 'headerHidden', new CFBoolean( isset( $eproject_sgs['_pr_phone_sgs_shelf_headerHidden'] ) ? $eproject_sgs['_pr_phone_sgs_shelf_headerHidden'] : true  ) );
    $phoneShelf->add( 'headerSticky', new CFBoolean( isset( $eproject_sgs['_pr_phone_sgs_shelf_headerSticky'] ) ? $eproject_sgs['_pr_phone_sgs_shelf_headerSticky'] : false ) );
    $phoneShelf->add( 'headerStretch', new CFBoolean( isset( $eproject_sgs['_pr_phone_sgs_shelf_headerStretch'] ) ? $eproject_sgs['_pr_phone_sgs_shelf_headerStretch'] : false ) );
    $phoneShelf->add( 'headerBackgroundColor', new CFString( isset( $eproject_sgs['_pr_phone_sgs_shelf_headerBackgroundColor'] ) ? $eproject_sgs['_pr_phone_sgs_shelf_headerBackgroundColor'] : 'clear' ) );
    $phoneShelf->add( 'headerImageFill', new CFBoolean( isset( $eproject_sgs['_pr_phone_sgs_shelf_headerImageFill'] ) ? $eproject_sgs['_pr_phone_sgs_shelf_headerImageFill'] : false ) );
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
    $portraitTitleLabel->add( 'align', new CFString( isset( $eproject_sgs['_pr_phone_sgs_portrait_titleLabelAlign'] ) ? $eproject_sgs['_pr_phone_sgs_portrait_titleLabelAlign'] : 'center'  ) );
    $portraitTitleLabel->add( 'font', new CFString( 'Lato-Regular' ) );
    $portraitTitleLabel->add( 'fontSize', new CFNumber( isset( $eproject_sgs['_pr_phone_sgs_portrait_titleLabelFontSize'] ) ? $eproject_sgs['_pr_phone_sgs_portrait_titleLabelFontSize'] : 17  ) );
    $portraitTitleLabel->add( 'color', new CFString( isset( $eproject_sgs['_pr_phone_sgs_portrait_titleLabelColor'] ) ? $eproject_sgs['_pr_phone_sgs_portrait_titleLabelColor'] : '#A99870' ) );

    $portraitTitleLabel->add( 'margin', $portraitTitleLabelMargin = new CFDictionary() );
    $portraitTitleLabelMargin->add( 'top', new CFNumber( 15 ) );
    $portraitTitleLabelMargin->add( 'right', new CFNumber( 0 ) );
    $portraitTitleLabelMargin->add( 'bottom', new CFNumber( 0 ) );
    $portraitTitleLabelMargin->add( 'left', new CFNumber( 0 ) );

    $phoneIssuePortrait->add( 'infoLabel', $portraitInfoLabel = new CFDictionary() );
    $portraitInfoLabel->add( 'align', new CFString( isset( $eproject_sgs['_pr_phone_sgs_portrait_infoLabelAlign'] ) ? $eproject_sgs['_pr_phone_sgs_portrait_infoLabelAlign'] : 'center'  ) );
    $portraitInfoLabel->add( 'font', new CFString( 'Lato-Regular' ) );
    $portraitInfoLabel->add( 'fontSize', new CFNumber( isset( $eproject_sgs['_pr_phone_sgs_portrait_infoLabelFontSize'] ) ? $eproject_sgs['_pr_phone_sgs_portrait_infoLabelFontSize'] : 14  ) );
    $portraitInfoLabel->add( 'color', new CFString( isset( $eproject_sgs['_pr_phone_sgs_portrait_infoLabelColor'] ) ? $eproject_sgs['_pr_phone_sgs_portrait_infoLabelColor'] : '#8C8C8C' ) );
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
    $portraitActionButton->add( 'fontSize', new CFNumber( isset( $eproject_sgs['_pr_phone_sgs_portrait_actionButtonFontSize'] ) ? $eproject_sgs['_pr_phone_sgs_portrait_actionButtonFontSize'] : 17  ) );
    $portraitActionButton->add( 'backgroundColor', new CFString( isset( $eproject_sgs['_pr_phone_sgs_portrait_actionButtonBackgroundColor'] ) ? $eproject_sgs['_pr_phone_sgs_portrait_actionButtonBackgroundColor'] : '#A99870'  ) );
    $portraitActionButton->add( 'textColor', new CFString( isset( $eproject_sgs['_pr_phone_sgs_portrait_actionButtonTextColor'] ) ? $eproject_sgs['_pr_phone_sgs_portrait_actionButtonTextColor'] : '#FFFFFF'  ) );

    $phoneIssuePortrait->add( 'archiveButton', $portraitArchiveButton = new CFDictionary() );
    $portraitArchiveButton->add( 'width', new CFNumber( 40 ) );
    $portraitArchiveButton->add( 'height', new CFNumber( 40 ) );
    $portraitArchiveButton->add( 'font', new CFString( 'Lato-Light' ) );
    $portraitArchiveButton->add( 'fontSize', new CFNumber( isset( $eproject_sgs['_pr_phone_sgs_portrait_archiveButtonFontSize'] ) ? $eproject_sgs['_pr_phone_sgs_portrait_archiveButtonFontSize'] : 17  ) );
    $portraitArchiveButton->add( 'backgroundColor', new CFString( isset( $eproject_sgs['_pr_phone_sgs_portrait_archiveButtonBackgroundColor'] ) ? $eproject_sgs['_pr_phone_sgs_portrait_archiveButtonBackgroundColor'] : '#A8A8A8'  ) );
    $portraitArchiveButton->add( 'textColor', new CFString( isset( $eproject_sgs['_pr_phone_sgs_portrait_archiveButtonTextColor'] ) ? $eproject_sgs['_pr_phone_sgs_portrait_archiveButtonTextColor'] : '#FFFFFF'  ) );

    $phoneIssuePortrait->add( 'cover', $portraitCover = new CFDictionary() );
    $portraitCover->add( 'backgroundColor', new CFString( isset( $eproject_sgs['_pr_phone_sgs_portrait_coverBackgroundColor'] ) ? $eproject_sgs['_pr_phone_sgs_portrait_coverBackgroundColor'] : '#FFFFFF'  ) );
    $portraitCover->add( 'borderColor', new CFString( isset( $eproject_sgs['_pr_phone_sgs_portrait_borderColor'] ) ? $eproject_sgs['_pr_phone_sgs_portrait_borderColor'] : '#979797'  ) );
    $portraitCover->add( 'borderSize', new CFNumber( 0 ) );
    $portraitCover->add( 'shadowOpacity', new CFNumber( 0 ) );

    $phoneIssuePortrait->add( 'buttonAlign', new CFString( isset( $eproject_sgs['_pr_phone_sgs_portrait_buttonAlign'] ) ? $eproject_sgs['_pr_phone_sgs_portrait_buttonAlign'] : 'center'  ) );

    $phoneIssuePortrait->add( 'buttonMargin', $phoneIssuePortraitMargin = new CFDictionary() );
    $phoneIssuePortraitMargin->add( 'top', new CFNumber( 10 ) );
    $phoneIssuePortraitMargin->add( 'right', new CFNumber( 10 ) );
    $phoneIssuePortraitMargin->add( 'bottom', new CFNumber( 30 ) );
    $phoneIssuePortraitMargin->add( 'left', new CFNumber( 10 ) );

    $phoneIssuePortrait->add( 'priceColor', new CFString( isset( $eproject_sgs['_pr_phone_sgs_portrait_priceColor'] ) ? $eproject_sgs['_pr_phone_sgs_portrait_priceColor'] : '#8c8c8c'  ) );
    $phoneIssuePortrait->add( 'loadingLabelColor', new CFString( isset( $eproject_sgs['_pr_phone_sgs_portrait_loadingLabelColor'] ) ? $eproject_sgs['_pr_phone_sgs_portrait_loadingLabelColor'] : '#97724a'  ) );
    $phoneIssuePortrait->add( 'loadingLabelFont', new CFString( 'Lato-Regular' ) );
    $phoneIssuePortrait->add( 'loadingLabelFontSize', new CFNumber( isset( $eproject_sgs['_pr_phone_sgs_portrait_loadingLabelFontSize'] ) ? $eproject_sgs['_pr_phone_sgs_portrait_loadingLabelFontSize'] : 11  )  );
    $phoneIssuePortrait->add( 'loadingSpinnerColor', new CFString( isset( $eproject_sgs['_pr_phone_sgs_portrait_loadingSpinnerColor'] ) ? $eproject_sgs['_pr_phone_sgs_portrait_loadingSpinnerColor'] : '#929292'  ) );
    $phoneIssuePortrait->add( 'progressBarTintColor', new CFString( isset( $eproject_sgs['_pr_phone_sgs_portrait_progressBarTintColor'] ) ? $eproject_sgs['_pr_phone_sgs_portrait_progressBarTintColor'] : '#97724a'  ) );
    $phoneIssuePortrait->add( 'progressBarBackgroundColor', new CFString( isset( $eproject_sgs['_pr_phone_sgs_portrait_progressBarBackgroundColor'] ) ? $eproject_sgs['_pr_phone_sgs_portrait_progressBarBackgroundColor'] : '#DDDDDD'  ) );

    /* Landscape */
    $phoneIssue->add( 'landscape', $phoneIssueLandscape = new CFDictionary() );
    $phoneIssueLandscape->add( 'cellHeight', new CFNumber( 0 ) );

    $phoneIssueLandscape->add( 'cellPadding', $landscapeCellpadding = new CFDictionary() );
    $landscapeCellpadding->add( 'top', new CFNumber( 20 ) );
    $landscapeCellpadding->add( 'right', new CFNumber( 15 ) );
    $landscapeCellpadding->add( 'bottom', new CFNumber( 0 ) );
    $landscapeCellpadding->add( 'left', new CFNumber( 15 ) );

    $phoneIssueLandscape->add( 'titleLabel', $landscapeTitleLabel = new CFDictionary() );
    $landscapeTitleLabel->add( 'align', new CFString( isset( $eproject_sgs['_pr_phone_sgs_landscape_titleLabelAlign'] ) ? $eproject_sgs['_pr_phone_sgs_landscape_titleLabelAlign'] : 'left'  ) );
    $landscapeTitleLabel->add( 'font', new CFString( 'Lato-Regular' ) );
    $landscapeTitleLabel->add( 'fontSize', new CFNumber( isset( $eproject_sgs['_pr_phone_sgs_landscape_titleLabelFontSize'] ) ? $eproject_sgs['_pr_phone_sgs_landscape_titleLabelFontSize'] : 17 ) );
    $landscapeTitleLabel->add( 'color', new CFString( isset( $eproject_sgs['_pr_phone_sgs_landscape_titleLabelColor'] ) ? $eproject_sgs['_pr_phone_sgs_landscape_titleLabelColor'] : '#A99870' ) );

    $landscapeTitleLabel->add( 'margin', $landscapeTitleLabelMargin = new CFDictionary() );
    $landscapeTitleLabelMargin->add( 'top', new CFNumber( 0 ) );
    $landscapeTitleLabelMargin->add( 'right', new CFNumber( 15 ) );
    $landscapeTitleLabelMargin->add( 'bottom', new CFNumber( 0 ) );
    $landscapeTitleLabelMargin->add( 'left', new CFNumber( 15 ) );

    $phoneIssueLandscape->add( 'infoLabel', $landscapeInfoLabel = new CFDictionary() );
    $landscapeInfoLabel->add( 'align', new CFString( isset( $eproject_sgs['_pr_phone_sgs_landscape_infoLabelAlign'] ) ? $eproject_sgs['_pr_phone_sgs_landscape_infoLabelAlign'] : 'left'  ) );
    $landscapeInfoLabel->add( 'font', new CFString( 'Lato-Regular' ) );
    $landscapeInfoLabel->add( 'fontSize', new CFNumber( isset( $eproject_sgs['_pr_phone_sgs_landscape_infoLabelfontSize'] ) ? $eproject_sgs['_pr_phone_sgs_landscape_infoLabelfontSize'] : 14 ) );
    $landscapeInfoLabel->add( 'color', new CFString( isset( $eproject_sgs['_pr_phone_sgs_landscape_infoLabelColor'] ) ? $eproject_sgs['_pr_phone_sgs_landscape_infoLabelColor'] : '#8C8C8C' ) );
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
    $landscapeActionButton->add( 'fontSize', new CFNumber( isset( $eproject_sgs['_pr_phone_sgs_landscape_actionButtonFontSize'] ) ? $eproject_sgs['_pr_phone_sgs_landscape_actionButtonFontSize'] : 17  ) );
    $landscapeActionButton->add( 'backgroundColor', new CFString( isset( $eproject_sgs['_pr_phone_sgs_landscape_actionButtonBackgroundColor'] ) ? $eproject_sgs['_pr_phone_sgs_landscape_actionButtonBackgroundColor'] : '#A99870'  ) );
    $landscapeActionButton->add( 'textColor', new CFString( isset( $eproject_sgs['_pr_phone_sgs_landscape_actionButtonTextColor'] ) ? $eproject_sgs['_pr_phone_sgs_landscape_actionButtonTextColor'] : '#FFFFFF'  ) );

    $phoneIssueLandscape->add( 'archiveButton', $landscapeArchiveButton = new CFDictionary() );
    $landscapeArchiveButton->add( 'width', new CFNumber( 40 ) );
    $landscapeArchiveButton->add( 'height', new CFNumber( 40 ) );
    $landscapeArchiveButton->add( 'font', new CFString( 'Lato-Regular' ) );
    $landscapeArchiveButton->add( 'fontSize', new CFNumber( isset( $eproject_sgs['_pr_phone_sgs_landscape_archiveButtonFontSize'] ) ? $eproject_sgs['_pr_phone_sgs_landscape_archiveButtonFontSize'] : 17  ) );
    $landscapeArchiveButton->add( 'backgroundColor', new CFString( isset( $eproject_sgs['_pr_phone_sgs_landscape_archiveButtonBackgroundColor'] ) ? $eproject_sgs['_pr_phone_sgs_landscape_archiveButtonBackgroundColor'] : '#A8A8A8'  ) );
    $landscapeArchiveButton->add( 'textColor', new CFString( isset( $eproject_sgs['_pr_phone_sgs_landscape_archiveButtonTextColor'] ) ? $eproject_sgs['_pr_phone_sgs_landscape_archiveButtonTextColor'] : '#FFFFFF'  ) );

    $phoneIssueLandscape->add( 'cover', $landscapeCover = new CFDictionary() );
    $landscapeCover->add( 'backgroundColor', new CFString( isset( $eproject_sgs['_pr_phone_sgs_landscape_coverBackgroundColor'] ) ? $eproject_sgs['_pr_phone_sgs_landscape_coverBackgroundColor'] : '#FFFFFF'  ) );
    $landscapeCover->add( 'borderColor', new CFString( isset( $eproject_sgs['_pr_phone_sgs_landscape_borderColor'] ) ? $eproject_sgs['_pr_phone_sgs_landscape_borderColor'] : '#979797'  ) );
    $landscapeCover->add( 'borderSize', new CFNumber( 0 ) );
    $landscapeCover->add( 'shadowOpacity', new CFNumber( 0 ) );

    $phoneIssueLandscape->add( 'buttonAlign', new CFString( isset( $eproject_sgs['_pr_phone_sgs_landscape_buttonAlign'] ) ? $eproject_sgs['_pr_phone_sgs_landscape_buttonAlign'] : 'left'  ) );

    $phoneIssueLandscape->add( 'buttonMargin', $phoneIssueLandscapeMargin = new CFDictionary() );
    $phoneIssueLandscapeMargin->add( 'top', new CFNumber( 10 ) );
    $phoneIssueLandscapeMargin->add( 'right', new CFNumber( 10 ) );
    $phoneIssueLandscapeMargin->add( 'bottom', new CFNumber( 20 ) );
    $phoneIssueLandscapeMargin->add( 'left', new CFNumber( 10 ) );

    $phoneIssueLandscape->add( 'priceColor', new CFString( isset( $eproject_sgs['_pr_phone_sgs_landscape_priceColor'] ) ? $eproject_sgs['_pr_phone_sgs_landscape_priceColor'] : '#8c8c8c'  ) );
    $phoneIssueLandscape->add( 'loadingLabelColor', new CFString( isset( $eproject_sgs['_pr_phone_sgs_landscape_loadingLabelColor'] ) ? $eproject_sgs['_pr_phone_sgs_landscape_loadingLabelColor'] : '#97724a'  ) );
    $phoneIssueLandscape->add( 'loadingLabelFont', new CFString( 'Lato-Regular' ) );
    $phoneIssueLandscape->add( 'loadingLabelFontSize', new CFNumber( isset( $eproject_sgs['_pr_phone_sgs_landscape_loadingLabelFontSize'] ) ? $eproject_sgs['_pr_phone_sgs_landscape_loadingLabelFontSize'] : 11  ) );
    $phoneIssueLandscape->add( 'loadingSpinnerColor', new CFString( isset( $eproject_sgs['_pr_phone_sgs_landscape_loadingSpinnerColor'] ) ? $eproject_sgs['_pr_phone_sgs_landscape_loadingSpinnerColor'] : '#929292'  ) );
    $phoneIssueLandscape->add( 'progressBarTintColor', new CFString( isset( $eproject_sgs['_pr_phone_sgs_landscape_progressBarTintColor'] ) ? $eproject_sgs['_pr_phone_sgs_landscape_progressBarTintColor'] : '#97724a'  ) );
    $phoneIssueLandscape->add( 'progressBarBackgroundColor', new CFString( isset( $eproject_sgs['_pr_phone_sgs_landscape_progressBarBackgroundColor'] ) ? $eproject_sgs['_pr_phone_sgs_landscape_progressBarBackgroundColor'] : '#DDDDDD'  ) );

    $phone->add( 'navigationBarOptions', $phoneNavBar = new CFDictionary() );

    $phoneNavBar->add( 'book', $phoneNavBook = new CFDictionary() );
    $phoneNavBook->add( 'tintColor', new CFString( isset( $eproject_sgs['_pr_phone_sgs_navBarBook_tintColor'] ) ? $eproject_sgs['_pr_phone_sgs_navBarBook_tintColor'] : '#A99870'  ) );
    $phoneNavBook->add( 'titleFontSize', new CFNumber( isset( $eproject_sgs['_pr_phone_sgs_navBarBook_titleFontSize'] ) ? $eproject_sgs['_pr_phone_sgs_navBarBook_titleFontSize'] : 16  ) );
    $phoneNavBook->add( 'titleFont', new CFString( 'Gotham-Book' ) );
    $phoneNavBook->add( 'titleColor', new CFString( isset( $eproject_sgs['_pr_phone_sgs_navBarBook_titleColor'] ) ? $eproject_sgs['_pr_phone_sgs_navBarBook_titleColor'] : '#A99870'  ) );
    $phoneNavBook->add( 'backgroundColor', new CFString( isset( $eproject_sgs['_pr_phone_sgs_navBarBook_backgroundColor'] ) ? $eproject_sgs['_pr_phone_sgs_navBarBook_backgroundColor'] : '#FFFFFF'  ) );
    $phoneNavBook->add( 'marginBottom', new CFNumber( 0 ) );

    $phoneNavBar->add( 'shelf', $phoneNavShelf = new CFDictionary() );
    $phoneNavShelf->add( 'tintColor', new CFString( isset( $eproject_sgs['_pr_phone_sgs_navBarShelf_tintColor'] ) ? $eproject_sgs['_pr_phone_sgs_navBarShelf_tintColor'] : '#FFFFFF'  ) );
    $phoneNavShelf->add( 'titleFontSize', new CFNumber( isset( $eproject_sgs['_pr_phone_sgs_navBarShelf_titleFontSize'] ) ? $eproject_sgs['_pr_phone_sgs_navBarShelf_titleFontSize'] : 16  ) );
    $phoneNavShelf->add( 'titleFont', new CFString( 'Gotham-Book' ) );
    $phoneNavShelf->add( 'titleColor', new CFString( isset( $eproject_sgs['_pr_phone_sgs_navBarShelf_titleColor'] ) ? $eproject_sgs['_pr_phone_sgs_navBarShelf_titleColor'] : '#FFFFFF'  ) );
    $phoneNavShelf->add( 'backgroundColor', new CFString( isset( $eproject_sgs['_pr_phone_sgs_navBarShelf_backgroundColor'] ) ? $eproject_sgs['_pr_phone_sgs_navBarShelf_backgroundColor'] : 'clear'  ) );
    $phoneNavShelf->add( 'marginBottom', new CFNumber( 30 ) );

    $phone->add( 'subscriptionViewOptions', $phoneSubscriptionViewOptions = new CFDictionary() );
    $phoneSubscriptionViewOptions->add( 'useNativeControl', new CFBoolean( false ) );
    $phoneSubscriptionViewOptions->add( 'backgroundColor', new CFString( isset( $eproject_sgs['_pr_phone_sgs_subView_backgroundColor'] ) ? $eproject_sgs['_pr_phone_sgs_subView_backgroundColor'] : '#FFFFFF'  ) );
    $phoneSubscriptionViewOptions->add( 'buttonWidth', new CFNumber( 0 ) );
    $phoneSubscriptionViewOptions->add( 'buttonHeight', new CFNumber( 40 ) );
    $phoneSubscriptionViewOptions->add( 'buttonTintColor', new CFString( isset( $eproject_sgs['_pr_phone_sgs_subView_buttonTintColor'] ) ? $eproject_sgs['_pr_phone_sgs_subView_buttonTintColor'] : '#999999'  ) );
    $phoneSubscriptionViewOptions->add( 'buttonTintColorHighlighted', new CFString( isset( $eproject_sgs['_pr_phone_sgs_subView_buttonTintColorHighlighted'] ) ? $eproject_sgs['_pr_phone_sgs_subView_buttonTintColorHighlighted'] : '#A99870'  ) );
    $phoneSubscriptionViewOptions->add( 'buttonBackgroundColor', new CFString( isset( $eproject_sgs['_pr_phone_sgs_subView_buttonBackgroundColor'] ) ? $eproject_sgs['_pr_phone_sgs_subView_buttonBackgroundColor'] : '#FFFFFF'  ) );

    $phoneSubscriptionViewOptions->add( 'margin', $phoneSubscriptionViewOptionsMargin = new CFDictionary() );
    $phoneSubscriptionViewOptionsMargin->add( 'top', new CFNumber( 10 ) );
    $phoneSubscriptionViewOptionsMargin->add( 'right', new CFNumber( 30 ) );
    $phoneSubscriptionViewOptionsMargin->add( 'bottom', new CFNumber( 10 ) );
    $phoneSubscriptionViewOptionsMargin->add( 'left', new CFNumber( 30 ) );

    $phoneSubscriptionViewOptions->add( 'buttonFont', new CFString( 'Lato-Light' ) );
    $phoneSubscriptionViewOptions->add( 'buttonFontSize', new CFNumber( isset( $eproject_sgs['_pr_phone_sgs_subView_buttonFontSize'] ) ? $eproject_sgs['_pr_phone_sgs_subView_buttonFontSize'] : 17  ) );
    $phoneSubscriptionViewOptions->add( 'separatorColor', new CFString( isset( $eproject_sgs['_pr_phone_sgs_subView_SeparatorColor'] ) ? $eproject_sgs['_pr_phone_sgs_subView_SeparatorColor'] : '#D8D8D8'  ) );
    $phoneSubscriptionViewOptions->add( 'separatorHeight', new CFNumber( 1 ) );

    $phone->add( 'authenticationViewOptions', $phoneAuthenticationViewOptions = new CFDictionary() );
    $phoneAuthenticationViewOptions->add( 'backgroundColor', new CFString( isset( $eproject_sgs['_pr_phone_sgs_authView_backgroundColor'] ) ? $eproject_sgs['_pr_phone_sgs_authView_backgroundColor'] : '#FFFFFF'  ) );
    $phoneAuthenticationViewOptions->add( 'fieldWidth', new CFNumber( 0 ) );
    $phoneAuthenticationViewOptions->add( 'fieldHeight', new CFNumber( 40 ) );
    $phoneAuthenticationViewOptions->add( 'fieldTextColor', new CFString( isset( $eproject_sgs['_pr_phone_sgs_authView_fieldTextColor'] ) ? $eproject_sgs['_pr_phone_sgs_authView_fieldTextColor'] : '#444444'  ) );
    $phoneAuthenticationViewOptions->add( 'fieldFont', new CFString( 'Cardo-Italic' ) );
    $phoneAuthenticationViewOptions->add( 'fieldFontSize', new CFNumber( isset( $eproject_sgs['_pr_phone_sgs_authView_fieldFontSize'] ) ? $eproject_sgs['_pr_phone_sgs_authView_fieldFontSize'] : 23  ) );

    $phoneAuthenticationViewOptions->add( 'fieldMargin', $phoneAuthenticationViewOptionsMargin = new CFDictionary() );
    $phoneAuthenticationViewOptionsMargin->add( 'top', new CFNumber( 10 ) );
    $phoneAuthenticationViewOptionsMargin->add( 'right', new CFNumber( 30 ) );
    $phoneAuthenticationViewOptionsMargin->add( 'bottom', new CFNumber( 10 ) );
    $phoneAuthenticationViewOptionsMargin->add( 'left', new CFNumber( 30 ) );

    $phoneAuthenticationViewOptions->add( 'fieldPlaceholderFont', new CFString( 'Cardo-Italic' ) );
    $phoneAuthenticationViewOptions->add( 'fieldPlaceholderColor', new CFString( isset( $eproject_sgs['_pr_phone_sgs_authView_fieldPlaceholderColor'] ) ? $eproject_sgs['_pr_phone_sgs_authView_fieldPlaceholderColor'] : '#999999'  ) );
    $phoneAuthenticationViewOptions->add( 'buttonFont', new CFString( 'Lato-Regular' ) );
    $phoneAuthenticationViewOptions->add( 'buttonFontSize', new CFNumber( isset( $eproject_sgs['_pr_phone_sgs_authView_buttonFontSize'] ) ? $eproject_sgs['_pr_phone_sgs_authView_buttonFontSize'] : 17  ) );
    $phoneAuthenticationViewOptions->add( 'buttonTintColor', new CFString( isset( $eproject_sgs['_pr_phone_sgs_authView_buttonTintColor'] ) ? $eproject_sgs['_pr_phone_sgs_authView_buttonTintColor'] : '#444444'  ) );
    $phoneAuthenticationViewOptions->add( 'buttonTintColorHighlighted', new CFString( isset( $eproject_sgs['_pr_phone_sgs_authView_buttonTintColorHighlighted'] ) ? $eproject_sgs['_pr_phone_sgs_authView_buttonTintColorHighlighted'] : '#A99870'  ) );
    $phoneAuthenticationViewOptions->add( 'buttonBackgroundColor', new CFString( isset( $eproject_sgs['_pr_phone_sgs_authView_buttonBackgroundColor'] ) ? $eproject_sgs['_pr_phone_sgs_authView_buttonBackgroundColor'] : '#FFFFFF'  ) );
    $phoneAuthenticationViewOptions->add( 'buttonBorderSize', new CFNumber( 1 ) );
    $phoneAuthenticationViewOptions->add( 'buttonBorderColor', new CFString( isset( $eproject_sgs['_pr_phone_sgs_authView_buttonBorderColor'] ) ? $eproject_sgs['_pr_phone_sgs_authView_buttonBorderColor'] : '#A6A6A6'  ) );
    $phoneAuthenticationViewOptions->add( 'labelTextColor', new CFString( isset( $eproject_sgs['_pr_phone_sgs_authView_labelTextColor'] ) ? $eproject_sgs['_pr_phone_sgs_authView_labelTextColor'] : '#444444'  ) );
    $phoneAuthenticationViewOptions->add( 'labelFont', new CFString( 'Lato-Regular' ) );
    $phoneAuthenticationViewOptions->add( 'labelFontSize', new CFNumber( isset( $eproject_sgs['_pr_phone_sgs_authView_labelFontSize'] ) ? $eproject_sgs['_pr_phone_sgs_authView_labelFontSize'] : 14  ) );
    $phoneAuthenticationViewOptions->add( 'separatorColor', new CFString( isset( $eproject_sgs['_pr_phone_sgs_authView_separatorColor'] ) ? $eproject_sgs['_pr_phone_sgs_authView_separatorColor'] : '#D8D8D8'  ) );
    $phoneAuthenticationViewOptions->add( 'separatorHeight', new CFNumber( 1 ) );

    /*
     * Save PList as XML
     */

    $plist->saveXML( PR_IOS_SETTINGS_PATH  . $eproject_slug . '.xml' );
    $this->_create_bundle( $eproject_slug, $eproject_id );

  }

  protected function _create_bundle( $eproject_slug, $eproject_id ) {

    $eproject_sgs = get_option( 'taxonomy_term_' . $eproject_id );
    $upload_dir = wp_upload_dir();

    $padBgImage = isset( $eproject_sgs['_pr_pad_sgs_shelf_backgroundImage'] ) ? get_attached_file( $eproject_sgs['_pr_pad_sgs_shelf_backgroundImage'] ) : false;
    $padLogo = isset( $eproject_sgs['_pr_pad_sgs_shelf_headerBackgroundImage'] ) ? get_attached_file( $eproject_sgs['_pr_pad_sgs_shelf_headerBackgroundImage'] ) : false;
    $padModalBgImage = isset( $eproject_sgs['_pr_pad_sgs_authView_modalLogoImage'] ) ? get_attached_file( $eproject_sgs['_pr_pad_sgs_authView_modalLogoImage'] ) : false;
    $phoneBgImage = isset( $eproject_sgs['_pr_phone_sgs_shelf_backgroundImage'] ) ? get_attached_file( $eproject_sgs['_pr_phone_sgs_shelf_backgroundImage'] ) : false;

    $atleast = false;
    $tmp_dir = PR_Utils::make_dir( PR_TMP_PATH, 'bundle_' . $eproject_slug );
    if( is_file( $padBgImage ) ) {
      copy( $padBgImage, $tmp_dir . DS .  'shelf-background-pad.png' );
      $atleast = true;
    }
    if( is_file( $padLogo ) ) {
      copy( $padLogo, $tmp_dir . DS .  'shelf-header.png' );
      $atleast = true;
    }
    if( is_file( $padModalBgImage ) ) {
      copy( $padModalBgImage, $tmp_dir . DS .  'modal-logo.png' );
      $atleast = true;
    }
    if( is_file( $phoneBgImage ) ) {
      copy( $phoneBgImage, $tmp_dir . DS .  'shelf-background-phone.png' );
      $atleast = true;
    }

    if( $atleast ) {
      $filename = PR_IOS_SETTINGS_PATH . $eproject_slug . '.images.zip';
      PR_Utils::create_zip_file( $tmp_dir, $filename, '' );
    }

    PR_Utils::remove_dir( $tmp_dir );

  }
}
 new pressroom_Plist;
