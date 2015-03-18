{**
 * @file plugins/importexport/ezid/templates/issues.tpl
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Select issues for export.
 *}
{strip}
{assign var="pageTitle" value="plugins.importexport.common.export.selectIssue"}
{assign var="pageCrumbTitle" value="plugins.importexport.common.export.selectIssue"}
{include file="common/header.tpl"}
{/strip}

<script type="text/javascript">{literal}
  function toggleChecked() {
    var elements = document.getElementById('issuesForm').elements;
    for (var i=0; i < elements.length; i++) {
      if (elements[i].name == 'issueId[]') {
        elements[i].checked = !elements[i].checked;
      }
    }
  }
{/literal}</script>

<br/>

<div id="issues">
  <form action="{plugin_url path="process"}" method="post" id="issuesForm">
    <input type="hidden" name="target" value="issue" />
    <table width="100%" class="listing">
      <tr>
        <td colspan="6" class="headseparator">&nbsp;</td>
      </tr>
      <tr class="heading" valign="bottom">
        <td width="5%">&nbsp;</td>
        <td width="55%">{translate key="issue.issue"}</td>
        <td width="15%">{translate key="editor.issues.published"}</td>
        <td width="15%">{translate key="editor.issues.numArticles"}</td>
        <td width="10%">{translate key="common.action"}</td>
        <td width="5%" align="center">{translate key="common.status"}</td>
      </tr>
      <tr>
        <td colspan="6" class="headseparator">&nbsp;</td>
      </tr>

      {iterate from=issues item=issue}
        {assign var="issueId" value=$issue->getId()}
        {if $allArticlesRegistered}
          {capture assign="updateOrRegister"}{translate key="plugins.importexport.ezid.update"}{/capture}
          {capture assign="updateOrRegisterDescription"}{translate key="plugins.importexport.ezid.updateDescription"}{/capture}
        {else}
          {capture assign="updateOrRegister"}{translate key="plugins.importexport.common.register"}{/capture}
          {capture assign="updateOrRegisterDescription"}{translate key="plugins.importexport.common.registerDescription"}{/capture}
        {/if}
        <tr valign="top">
          <td><input type="checkbox" name="issueId[]" value="{$issue->getId()}"/></td>
          <td><a href="{url page="issue" op="view" path=$issue->getId()}" class="action">{$issue->getIssueIdentification()|strip_unsafe_html|nl2br}</a></td>
          <td>{$issue->getDatePublished()|date_format:"$dateFormatShort"|default:"&mdash;"}</td>
          <td>{$numArticles[$issueId]|escape}</td>
          <td align="right"><nobr>
            {if $hasCredentials}
              <a href="{plugin_url path="process" issueId=$issue->getId() params=$testMode target="issue" register=true}" title="{$updateOrRegisterDescription}" class="action">{$updateOrRegister}</a>
            {/if}
            <a href="{plugin_url path="process" issueId=$issue->getId() params=$testMode target="issue" export=true}" title="{translate key="plugins.importexport.common.exportDescription"}" class="action">{translate key="common.export"}</a>
          </nobr></td>
          <td align="center">
            {if $issue->getData('ezid::registeredDoi')}
              <a href="http://dx.doi.org/{$issue->getStoredPubId('doi')|escape}" target="_blank">doi:{$issue->getStoredPubId('doi')|escape}</a>
            {else}
              -
            {/if}
          </td>
        </tr>
        <tr>
          <td colspan="6" class="{if $issues->eof()}end{/if}separator">&nbsp;</td>
        </tr>
      {/iterate}
      {if $issues->wasEmpty()}
        <tr>
          <td colspan="6" class="nodata">{translate key="plugins.importexport.common.export.noIssues"}</td>
        </tr>
        <tr>
          <td colspan="6" class="endseparator">&nbsp;</td>
        </tr>
      {else}
        <tr>
          <td colspan="2" align="left">{page_info iterator=$issues}</td>
          <td colspan="4" align="right">{page_links anchor="issues" name="issues" iterator=$issues}</td>
        </tr>
      {/if}
    </table>
    <p>
      {if !empty($testMode)}<input type="hidden" name="testMode" value="1" />{/if}
      {if $hasCredentials}
        <input type="submit" name="register" value="{translate key="plugins.importexport.common.register"}" title="{translate key="plugins.importexport.common.registerDescription.multi"}" class="button defaultButton"/>
        &nbsp;
      {/if}
      <input type="submit" name="export" value="{translate key="common.export"}" title="{translate key="plugins.importexport.common.exportDescription"}" class="button{if !$hasCredentials}  defaultButton{/if}"/>
      &nbsp;
      <input type="button" value="{translate key="common.selectAll"}" class="button" onclick="toggleChecked()" />
    </p>
    <p>
      {if $hasCredentials}
        {translate key="plugins.importexport.common.register.warning"}
      {else}
        {capture assign="settingsUrl"}{plugin_url path="settings"}{/capture}
        {translate key="plugins.importexport.common.register.noCredentials" settingsUrl=$settingsUrl}
      {/if}
    </p>
  </form>
</div>

{include file="common/footer.tpl"}
