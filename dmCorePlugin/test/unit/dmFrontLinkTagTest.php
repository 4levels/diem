<?php

require_once(dirname(__FILE__).'/helper/dmUnitTestHelper.php');
$helper = new dmUnitTestHelper();
$helper->boot('front');

if(sfConfig::get('sf_app') == 'front' && class_exists('dmFrontPluginConfiguration', false))
{
  $t = new lime_test(20);
}
else
{
  $t = new lime_test(1);
  $t->pass('Work only in standalone test');
  return;
}

dmDb::table('DmPage')->checkBasicPages();

$sc = $helper->get('service_container');

$sc->mergeParameter('link_tag_record.options', array(
  'current_span'=> false
))->mergeParameter('link_tag_page.options', array(
  'current_span'=> false
));
$t->diag('link current_span is false');

$home = dmDb::table('DmPage')->getTree()->fetchRoot();
$currentPage = $home;
dmContext::getInstance()->setPage($currentPage);
$t->diag($home->name.' is the current page');

$testPage = dmDb::create('DmPage', array(
  'module' => 'main',
  'action' => 'test'.dmString::random(12),
  'name'   => 'I am a root child',
  'slug'   => dmString::random()
));

$testPage->Node->insertAsLastChildOf($home);

sfContext::getInstance()->getConfiguration()->loadHelpers(array('Dm'));

$scriptName = $helper->get('request')->getScriptName();
$t->diag('Current cli script name = '.$scriptName);

$t->is((string)£link(), (string)£link($home), '£link($home) is £link()');

$t->is(£link()->getHref(), $scriptName, 'root href is '.$scriptName);

$t->is(£link()->getText(), $home->name, 'root link text is '.$home->name);

$expected = dmContext::getInstance()->get('controller')->genUrl('dmAuth/signin');
$t->is(£link('+/dmAuth/signin')->getHref(), $expected, '+/dmAuth/signin href is '.$expected);

$rootLink = sprintf('<a class="%s" href="%s">%s</a>', 'link dm_current', $scriptName, $home->name);
$t->is((string)£link(), $rootLink, 'root link is '.$rootLink);

$hrefWithParam = $scriptName.'?var=val&other=value';
$t->is((string)£link()->param('var', 'val')->param('other', 'value')->getHref(), $hrefWithParam, $hrefWithParam);
$t->is((string)£link()->params(array('var' => 'val', 'other' => 'value'))->getHref(), $hrefWithParam, $hrefWithParam);

$absoluteHrefWithParam = 'http://'.$scriptName.'?var=val';
$t->is((string)£link()->param('var', 'val')->getAbsoluteHref(), $absoluteHrefWithParam, $absoluteHrefWithParam);

$absoluteHrefWithParam2 = 'http://'.$scriptName.'?var=val&var2=val2';
$t->is((string)£link($absoluteHrefWithParam)->param('var2', 'val2')->getHref(), $absoluteHrefWithParam2, $absoluteHrefWithParam2);

$absoluteHrefWithParam3 = 'http://'.$scriptName.'?var=val&var2=changed_value';
$t->is((string)£link($absoluteHrefWithParam)->param('var2', 'changed_value')->getHref(), $absoluteHrefWithParam3, $absoluteHrefWithParam3);

$linkWithParam2 = sprintf('<a class="%s" href="%s">%s</a>', 'link', str_replace("&", "&amp;", $absoluteHrefWithParam2), 'abs link with params');
$t->is((string)£link($absoluteHrefWithParam2)->text('abs link with params'), $linkWithParam2, $linkWithParam2);

$testPageLink = sprintf('<a class="%s" href="%s">%s</a>', 'link', $scriptName.'/'.$testPage->slug, $testPage->name);
$t->is((string)£link($testPage), $testPageLink, 'page link is '.$testPageLink);

dmContext::getInstance()->setPage($testPage);
$t->diag($testPage->name.' is the current page');

$testPageLink = sprintf('<a class="%s" href="%s">%s</a>', 'link dm_current', $scriptName.'/'.$testPage->slug, $testPage->name);
$t->is((string)£link($testPage), $testPageLink, 'page link is '.$testPageLink);

$rootLink = sprintf('<a class="%s" href="%s">%s</a>', 'link dm_parent', $scriptName, $home->name);
$t->is((string)£link(), $rootLink, 'root link is '.$rootLink);

$sc->mergeParameter('link_tag_record.options', array(
  'current_span'=> true
))->mergeParameter('link_tag_page.options', array(
  'current_span'=> true
));
$t->diag('link current_span is true');

$testPageLink = sprintf('<span class="%s">%s</span>', 'link dm_current', $testPage->name);
$t->is((string)£link($testPage), $testPageLink, 'page link is '.$testPageLink);

$testPageLink = sprintf('<span class="%s">%s</span>', 'link dm_current', $testPage->name);
$t->is((string)£link('page:'.$testPage->id), $testPageLink, 'page:'.$testPage->id.' link is '.$testPageLink);

$testPage->Node->delete();

//$t->diag('Switch app');
//
//$adminUrl = 'http://symfony/admin_test.php';
//$t->is(£link('app:admin')->getHref(), $adminUrl, $adminUrl);
//
//$adminUrl2 = 'http://symfony/admin_test.php/main/test';
//$t->is(£link('app:admin/main/test')->getHref(), $adminUrl2, $adminUrl2);
//
//$adminUrl3 = 'http://symfony/admin_test.php?var1=val2';
//$t->is(£link('app:admin?var1=val2')->getHref(), $adminUrl3, $adminUrl3);

$t->diag('blank');

$blankLink = sprintf('<a class="link" target="%s" href="%s">%s</a>', '_blank', 'http://iliaz.com', 'http://iliaz.com');
$t->is((string)£link('http://iliaz.com')->target('blank'), $blankLink, 'blank link is '.$blankLink);

$blankLink = sprintf('<a class="link" href="%s">%s</a>', 'http://iliaz.com', 'http://iliaz.com');
$t->is((string)£link('http://iliaz.com')->target('blank')->target(false), $blankLink, 'canceled blank link is '.$blankLink);

$t->diag('media links');
$media = dmDb::table('DmMedia')->findOne();

$mediaLink = sprintf('<a class="link" href="%s">%s</a>', $helper->get('request')->getAbsoluteUrlRoot().'/'.$media->webPath, $media->file);
$t->is((string)£link($media), $mediaLink, $mediaLink);
$t->is((string)£link('media:'.$media->id), $mediaLink, $mediaLink);