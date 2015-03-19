<?php

/**
 * @file plugins/importexport/ezid/ezidExportPlugin.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ezidExportPlugin
 * @ingroup plugins_importexport_ezid
 *
 * @brief ezid export/registration plugin.
 */


if (!class_exists('DOIExportPlugin')) { // Bug #7848
  import('plugins.importexport.crossref.classes.DOIExportPlugin');
}

import('lib.pkp.classes.webservice.WebService');

// EZID API
define('EZID_API_RESPONSE_CREATED', 201);
define('EZID_API_MINT_URL', 'https://ezid.cdlib.org/shoulder/doi:');
define('EZID_API_MODIFY_URL', 'https://ezid.cdlib.org/id/doi:');

class EzidRegisterPlugin extends DOIExportPlugin {

  //
  // Constructor
  //
  function EzidRegisterPlugin() {
    parent::DOIExportPlugin();
  }


  //
  // Implement template methods from ImportExportPlugin
  //
  /**
   * @see ImportExportPlugin::getName()
   */
  function getName() {
    return 'EzidRegisterPlugin';
  }

  /**
   * @see ImportExportPlugin::getDisplayName()
   */
  function getDisplayName() {
    return __('plugins.importexport.ezid.displayName');
  }

  /**
   * @see ImportExportPlugin::getDescription()
   */
  function getDescription() {
    return __('plugins.importexport.ezid.description');
  }

  //
  // Implement template methods from DOIExportPlugin
  //
  /**
   * @see DOIExportPlugin::getPluginId()
   */
  function getPluginId() {
    return 'ezid';
  }

  /**
   * @see DOIExportPlugin::getSettingsFormClassName()
   */
  function getSettingsFormClassName() {
    return 'EzidSettingsForm';
  }

  /**
   * @see DOIExportPlugin::getAllObjectTypes()
   */
  function getAllObjectTypes() {
    return array(
      'issue' => DOI_EXPORT_ISSUES,
      'article' => DOI_EXPORT_ARTICLES
    );
  }

  /**
   * Display a list of issues for export.
   * @param $templateMgr TemplateManager
   * @param $journal Journal
   */
  function displayIssueList(&$templateMgr, &$journal) {
    $this->setBreadcrumbs(array(), true);

    // Retrieve all published issues.
    AppLocale::requireComponents(array(LOCALE_COMPONENT_OJS_EDITOR));
    $issueDao =& DAORegistry::getDAO('IssueDAO'); /* @var $issueDao IssueDAO */
    $this->registerDaoHook('IssueDAO');
    $issueIterator =& $issueDao->getPublishedIssues($journal->getId(), Handler::getRangeInfo('issues'));

    // Filter only issues that contain an article that have a DOI assigned.
    $publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
    $issues = array();
    $numArticles = array();
    while ($issue =& $issueIterator->next()) {
      $issueArticles =& $publishedArticleDao->getPublishedArticles($issue->getId());
      $issueArticlesNo = 0;
      $allArticlesRegistered = true;
      foreach ($issueArticles as $issueArticle) {
        $articleRegistered = $issueArticle->getData('crossref::registeredDoi');
        if ($issueArticle->getPubId('doi') && !isset($articleRegistered)) {
          if (!in_array($issue, $issues)) $issues[] = $issue;
          $issueArticlesNo++;
        }
        if ($allArticlesRegistered && !isset($articleRegistered)) {
          $allArticlesRegistered = false;
        }
      }
      $numArticles[$issue->getId()] = $issueArticlesNo;
    }

    // Instantiate issue iterator.
    import('lib.pkp.classes.core.ArrayItemIterator');
    $rangeInfo = Handler::getRangeInfo('articles');
    $iterator = new ArrayItemIterator($issues, $rangeInfo->getPage(), $rangeInfo->getCount());

    // Prepare and display the issue template.
    $templateMgr->assign_by_ref('issues', $iterator);
    $templateMgr->assign('numArticles', $numArticles);
    $templateMgr->assign('allArticlesRegistered', $allArticlesRegistered);
    $templateMgr->display($this->getTemplatePath() . 'issues.tpl');
  }

  /**
   * @see DOIExportPlugin::displayAllUnregisteredObjects()
   */
  function displayAllUnregisteredObjects(&$templateMgr, &$journal) {
    // Prepare information specific to this plug-in.
    $this->setBreadcrumbs(array(), true);
    AppLocale::requireComponents(array(LOCALE_COMPONENT_PKP_SUBMISSION));

    // Prepare and display the template.
    $templateMgr->assign_by_ref('articles', $this->_getUnregisteredArticles($journal));
    $templateMgr->display($this->getTemplatePath() . 'unregisteredArticles.tpl');
  }

  /**
   * @copydoc DOIExportPlugin::displayArticleList
   */
  function displayArticleList(&$templateMgr, &$journal) {
    return parent::displayArticleList($templateMgr, $journal);
  }

  /**
   * The selected issue can be exported if it contains an article that has a DOI,
   * and the articles containing a DOI also have a date published.
   * The selected article can be exported if it has a DOI and a date published.
   * @param $foundObject Issue|PublishedArticle
   * @param $errors array
   * @return array|boolean
  */
  function canBeExported($foundObject, &$errors) {
    if (is_a($foundObject, 'Issue')) {
      $export = false;
      $publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
      $issueArticles =& $publishedArticleDao->getPublishedArticles($foundObject->getId());
      foreach ($issueArticles as $issueArticle) {
        if (!is_null($issueArticle->getPubId('doi'))) {
          $export = true;
          if (is_null($issueArticle->getDatePublished())) {
            $errors[] = array('plugins.importexport.crossref.export.error.articleDatePublishedMissing', $issueArticle->getId());
            return false;
          }
        }
      }
      return $export;
    }
    if (is_a($foundObject, 'PublishedArticle')) {
      if (is_null($foundObject->getDatePublished())) {
        $errors[] = array('plugins.importexport.crossref.export.error.articleDatePublishedMissing', $foundObject->getId());
        return false;
      }
      return parent::canBeExported($foundObject, $errors);
    }
  }

  /**
   * @see DOIExportPlugin::generateExportFiles()
   */
  function generateExportFiles(&$request, $exportType, &$objects, $targetPath, &$journal, &$errors) {
    // Additional locale file.
    AppLocale::requireComponents(array(LOCALE_COMPONENT_OJS_EDITOR));

    $this->import('classes.EzidExportDom');
    $dom = new EzidExportDom($request, $this, $journal, $this->getCache());
    $doc =& $dom->generate($objects);
    if ($doc === false) {
      $errors =& $dom->getErrors();
      return false;
    }

    // Write the result to the target file.
    $exportFileName = $this->getTargetFileName($targetPath, $exportType);
    file_put_contents($exportFileName, XMLCustomWriter::getXML($doc));
    $generatedFiles = array($exportFileName => &$objects);
    return $generatedFiles;
  }

    /**
   * Register publishing objects.
   *
   * @param $request Request
   * @param $exportSpec array An array with DOI_EXPORT_* constants as keys and
   *  object ids as values.
   * @param $journal Journal
   *
   * @return boolean|array True for success or an array of error messages.
   */
  function registerObjects(&$request, $exportSpec, &$journal) {
    // Registering can take a long time.
    @set_time_limit(0);

    // Get the target directory.
    $result = $this->_getExportPath();
    if (is_array($result)) return $result;
    $exportPath = $result;
    // Run through the export spec and generate XML files and register dois for the corresponding
    // objects.
    $errors = array();
    
    // Run through the export types and generate the corresponding
    // export files.
    $exportFiles = array();
    foreach($exportSpec as $exportType => $objectIds) {
      // Normalize the object id(s) into an array.
      if (is_scalar($objectIds)) $objectIds = array($objectIds);

      // Retrieve the object(s).
      $objects =& $this->_getObjectsFromIds($exportType, $objectIds, $journal->getId(), $errors);
      if (empty($objects)) {
        $this->cleanTmpfiles($exportPath, $exportFiles);
        return errors;
      }
      $result = $this->processRegisterObjects($request, $exportType, $objects, $exportPath, $journal, $errors);
      if ($result !== true) {
        return $result;
      }  
    }
    return true;
  }

  function processRegisterObjects(&$request, $exportType, &$objects, $exportPath, &$journal, &$errors) {
    // Run through the export types and generate the corresponding
    // export files.
    $exportFiles = array();
    
    // Export the object(s) to file(s).
    // Additional locale file.
    AppLocale::requireComponents(array(LOCALE_COMPONENT_OJS_EDITOR));

    $this->import('classes.EzidExportDom');
    foreach($objects as $object) {
      // Write the result to the target file.
      $exportFileName = $this->getTargetFileName($exportPath, $exportType, $object->getId());

      $dom = new EzidExportDom($request, $this, $journal, $this->getCache());
      $object_array = array();
      array_push($object_array, $object);
      $doc =& $dom->generate($object_array);
      if ($doc === false) {
        $errors =& $dom->getErrors();
        return $errors;
      }
      
      file_put_contents($exportFileName, XMLCustomWriter::getXML($doc));
      $result = $this->registerDoi($request, $journal, $object, $exportFileName);
      if ($result !== true) {
        $this->cleanTmpfiles($exportPath, $exportFiles);
        return $result;
      }
    }
    // Remove all temporary files.
    $this->cleanTmpfiles($exportPath, $exportFiles);
    return true;
  }

  /**
   * @see DOIExportPlugin::registerDoi()
   */
  function registerDoi(&$request, &$journal, &$object, $filename) {
    $shoulder = $this->getSetting($journal->getId(), 'shoulder');
    // Transmit CrossRef XML metadata.
    assert(is_readable($filename));
    $payload = file_get_contents($filename);
    assert($payload !== false && !empty($payload));
    // we only consider articles and issues here
    $result = true;

    if (is_a($object, 'PublishedArticle') || is_a($object, 'Issue') ) {      
      
      $input = "_profile: crossref" . PHP_EOL;
      $input .= "crossref: " . $this->_doiMetadataEscape($payload) . PHP_EOL;
      
      // TODO: SHOW BOTH DATACITE METADATA AS WELL
      //5 required datacite fields:
      $input .= "datacite.creator: ";
      if (is_a($object, 'PublishedArticle')) {
        foreach ($object->getAuthors() as $author) {
          $input .= $author->getLastName() . ", " . $author->getFirstName() . " " . $author->getMiddleName() . "; ";
        }
      } 
      $input .= PHP_EOL;
      $input .= "datacite.title: " . $object->getLocalizedTitle() . PHP_EOL;
      $input .= "datacite.publisher: " . $journal->getSetting('publisherInstitution') . PHP_EOL;
      $input .= "datacite.publicationyear: " . date('Y', strtotime($object->getDatePublished())) . PHP_EOL;
      $input .= "datacite.resourcetype: " . $object->getLocalizedData('type'). PHP_EOL;  
      if ($object->getData('ezid::registeredDoi'))
        $webServiceRequest = new WebServiceRequest(EZID_API_MODIFY_URL . $object->getStoredPubId('doi'), $input, 'POST');
      else
        $webServiceRequest = new WebServiceRequest(EZID_API_MINT_URL . $shoulder, $input, 'POST');
      $webServiceRequest->setHeader('Content-Type', 'text/plain; charset=UTF-8');
      $webServiceRequest->setHeader('Content-Length', strlen($input));
  
      $webService = new WebService();
      $username = $this->getSetting($journal->getId(), 'username');
      $password = $this->getSetting($journal->getId(), 'password');
      $webService->setAuthUsername($username);
      $webService->setAuthPassword($password);
      $response =& $webService->call($webServiceRequest);

      
      if ($response === false) {
        $result = array(array('plugins.importexport.common.register.error.mdsError', 'No response from server.'));
      } else {
        $status = $webService->getLastResponseStatus();
        if ($status != EZID_API_RESPONSE_CREATED) {
          $result = array(array('plugins.importexport.common.register.error.mdsError', "$status - $response"));
        }
      }
    } else {
      return false;
    }

    if ($result === true) {
      # trim off "success: doi:"
      $trimmed_body = preg_replace('/(success: doi:)/', '', $response);
      list($doi, $ark) = explode(' ', $trimmed_body, 2);
      
      if (is_a($object, 'Issue')) {
        $dao =& DAORegistry::getDAO('IssueDAO');
        $dao->changePubId($object->getId(), 'doi', $doi);
        $object->setStoredPubId('doi', $doi);
      } elseif (is_a($object, 'Article')) {
        $dao =& DAORegistry::getDAO('ArticleDAO');
        $dao->changePubId($object->getId(), 'doi', $doi);
        $object->setStoredPubId('doi', $doi);
      } 
      // Mark the object as registered.
      $this->markRegistered($request, $object, $shoulder);        
    }

    return $result;
  }
  
  //
  // Private helper methods
  //

  /**
   * Encode DOI according to ANSI/NISO Z39.84-2005, Appendix E.
   * @param $pubId string
   * @return string
   */
  function _doiMetadataEscape($anvl_string) {
    $search = array ("%", ":", "\r", "\n");
    $replace = array ("%25", "%3A", "%0D", "%0A");
    $anvl_string = str_replace($search, $replace, $anvl_string);
    return $anvl_string;
  }
  
  /**
   * @see DOIExportPlugin::processMarkRegistered()
   */
  function processMarkRegistered(&$request, $exportType, &$objects, &$journal) {
    $this->import('classes.EzidExportDom');
    $dom = new EzidExportDom($request, $this, $journal, $this->getCache());
    foreach($objects as $object) {
      if (is_a($object, 'Issue')) {
        $articlesByIssue =& $dom->retrieveArticlesByIssue($object);
        foreach ($articlesByIssue as $article) {
          if ($article->getPubId('doi')) {
            $this->markRegistered($request, $article);
          }
        }
      } else {
        if ($object->getPubId('doi')) {
          $this->markRegistered($request, $object);
        }
      }
    }
  }

  /**
   * Mark an object as "registered"
   * by saving it's DOI to the object's
   * "registeredDoi" setting.
   * We prefix the setting with the plug-in's
   * id so that we do not get name clashes
   * when several DOI registration plug-ins
   * are active at the same time.
   * @parem $request Request
   * @param $object Issue|PublishedArticle|ArticleGalley|SuppFile
   * @parem $testPrefix string
   */
  function markRegistered(&$request, &$object, $testPrefix = '10.5072/FK2') {
    $registeredDoi = $object->getPubId('doi');
    assert(!empty($registeredDoi));
    if ($this->isTestMode($request)) {
      $registeredDoi = String::regexp_replace('#^[^/]+/#', $testPrefix, $registeredDoi);
    }
    $this->saveRegisteredDoi($object, $registeredDoi);
  }
}

?>
