{**
 * @file plugins/importexport/ezid/templates/settings.tpl
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Ezid plugin settings
 *}
{strip}
{assign var="pageTitle" value="plugins.importexport.common.settings"}
{include file="common/header.tpl"}
{/strip}
<div id="ezidSettings">
  {include file="common/formErrors.tpl"}
  <p>{translate key="plugins.importexport.ezid.registrationIntro"}</p>
  <h3>{translate key="plugins.importexport.ezid.requirements"}</h3>
  <br />

  {capture assign="settingsUrl"}{plugin_url path="settings"}{/capture}
  {capture assign="unregisteredArticlesUrl"}{plugin_url op='importexport' path="all"}{/capture}
  {url|assign:"publisherUrl" page="manager" op="setup" path="1" anchor='setupPublisher'}
  {url|assign:"issnUrl" page="manager" op="setup" path="1" anchor='generalInformation'}
  {url|assign:"doiUrl" page="manager" op="plugin" path="pubIds"}

  {if !empty($configurationErrors) || !$currentJournal->getSetting('publisherInstitution')|escape || !$currentJournal->getSetting('onlineIssn')|escape}
  <ul>
    {foreach from=$configurationErrors item=configurationError}
      {if $configurationError == $smarty.const.DOI_EXPORT_CONFIGERROR_DOIPREFIX}
        <li>{translate key="plugins.importexport.ezid.error.DOIsNotAvailable" doiUrl=$doiUrl}</li>
      {elseif $configurationError == $smarty.const.DOI_EXPORT_CONFIGERROR_SETTINGS}
        <li>{translate key="plugins.importexport.ezid.error.pluginNotConfigured" settingsUrl=$settingsUrl}</li>
      {/if}
    {/foreach}
    {if !$currentJournal->getSetting('publisherInstitution')|escape}
      <li>{translate key="plugins.importexport.ezid.error.publisherNotConfigured" publisherUrl=$publisherUrl}</li>
    {/if}
    {if !$currentJournal->getSetting('onlineIssn')|escape}
      <li>{translate key="plugins.importexport.ezid.error.issnNotConfigured" issnUrl=$issnUrl}</li>
    {/if}
  </ul>
  {else}
    {translate key="plugins.importexport.ezid.requirements.satisfied"}
  {/if}

  <h3>{translate key="plugins.importexport.common.settings"}</h3>
  <br />
  <form method="post" action="{plugin_url path="settings"}">
    <table width="100%" class="data">
      <tr valign="top">
        <td colspan="2">
          <span class="instruct">{translate key="plugins.importexport.ezid.registrationIntro"}</span>
        </td>
      </tr>
      <tr><td colspan="2">&nbsp;</td></tr>
      <tr valign="top">
        <td width="20%" class="label">{fieldLabel name="username" key="plugins.importexport.ezid.settings.form.username"}</td>
        <td width="80%" class="value">
          <input type="text" name="username" value="{$username|escape}" size="20" maxlength="50" id="username" class="textField" />
          <br />{translate key="plugins.importexport.ezid.settings.form.username.description"}
        </td>
      </tr>
      <tr><td colspan="2">&nbsp;</td></tr>
      <tr valign="top">
        <td width="20%" class="label">{fieldLabel name="password" key="plugins.importexport.common.settings.form.password"}</td>
        <td width="80%" class="value">
          <input type="password" name="password" value="{$password|escape}" size="20" maxlength="50" id="password" class="textField" />
          <br />{translate key="plugins.importexport.ezid.settings.form.password.description"}
        </td>
      </tr>
      <tr><td colspan="2">&nbsp;</td></tr>
      <tr valign="top">
        <td width="20%" class="label">{fieldLabel name="shoulder" key="plugins.importexport.ezid.settings.form.shoulder"}</td>
        <td width="80%" class="value">
          <input type="shoulder" name="shoulder" value="{$shoulder|escape}" size="20" maxlength="50" id="shoulder" class="textField" />
          <br />{translate key="plugins.importexport.ezid.settings.form.shoulder.description"}
        </td>
      </tr>
      <tr><td colspan="2">&nbsp;</td></tr>
      <tr valign="top">
        <td width="20%" class="label">{fieldLabel name="automaticRegistration" key="plugins.importexport.ezid.settings.form.automaticRegistration"}</td>
        <td width="80%" class="value">
          <input type="checkbox" name="automaticRegistration" id="automaticRegistration" value="1" {if $automaticRegistration} checked="checked"{/if} />&nbsp;{translate key="plugins.importexport.ezid.settings.form.automaticRegistration.description" unregisteredArticlesUrl=$unregisteredArticlesUrl}
        </td>
      </tr>
      <tr><td colspan="2">&nbsp;</td></tr>
    </table>

    <p><span class="formRequired">{translate key="common.requiredField"}</span></p>

    <p>
      <input type="submit" name="save" class="button defaultButton" value="{translate key="common.save"}"/>
      &nbsp;
      <input type="button" class="button" value="{translate key="common.cancel"}" onclick="history.go(-1)"/>
    </p>
  </form>

</div>
{include file="common/footer.tpl"}
