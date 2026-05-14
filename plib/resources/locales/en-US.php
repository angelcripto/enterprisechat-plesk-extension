<?php
return [
    // Header and tabs
    'pageTitle'             => 'EnterpriseChat',
    'tabStatus'             => 'Status',
    'tabDomains'            => 'Domains',
    'tabConfig'             => 'Configuration',

    // Service card
    'serviceTitle'          => 'Service',
    'lblState'              => 'State',
    'lblEnabled'            => 'Start at boot',
    'lblSince'              => 'Active since',

    // License card
    'licenseTitle'          => 'License',
    'licenseUnavailable'    => 'The server is not responding. Check the service status.',
    'lblEdition'            => 'Edition',
    'lblMaxUsers'           => 'Max concurrent users',
    'btnConfigEdit'         => 'Edit configuration',

    // Bindings card
    'bindingsTitle'         => 'Bound domains',
    'bindingsEmpty'         => 'No domains bound yet.',
    'btnManageDomains'      => 'Manage domains',
    'btnBack'               => 'Back to status',
    'createSubdomainTitle'  => 'Create a new subdomain',
    'createSubdomainHelp'   => 'Recommended: create a dedicated subdomain (e.g. chat.yourdomain.com) so the chat does not share root paths with your main site.',
    'lblSubdomainPrefix'    => 'Subdomain prefix',
    'lblParentDomain'       => 'Parent domain',
    'btnCreateAndBind'      => 'Create and bind',
    'bindExistingTitle'     => 'Bind on an existing domain',
    'bindExistingHelp'      => 'Mount the chat under a path of the selected domain (default /chat/). Useful if you do not want to create a subdomain.',
    'bindNewTitle'          => 'Bind a domain',
    'currentBindingsTitle'  => 'Current bindings',
    'lblDomain'             => 'Domain',
    'lblLocation'           => 'Location (default /chat/)',
    'btnBind'               => 'Bind',
    'btnUnbind'             => 'Unbind',
    'confirmUnbind'         => 'Remove the reverse proxy from this domain?',
    'msgBound'              => 'Domain %d% bound successfully.',
    'msgUnbound'            => 'Domain %d% unbound.',

    // Install pending banner
    'installPendingTitle'   => 'Installation not finished yet',
    'installPendingHelp'    => 'Plesk could not run the installer with root privileges. SSH into the VPS as root and run this command to finish the deployment:',
    'installPendingAfter'   => 'After running it, reload this page: the initial admin password will be shown.',

    // Initial password reveal
    'firstPasswordTitle'    => 'Initial admin password',
    'firstPasswordHelp'     => 'Username "admin". Change it right after the first login.',
    'firstPasswordOnce'     => 'This password is shown only once. If lost, regenerate it from the configuration page.',

    // Configuration screen
    'configTitle'           => 'Server configuration',
    'sectionJwt'            => 'Session / JWT',
    'lblLifetime'           => 'Token lifetime (minutes)',
    'hintLifetime'          => 'Between 5 and 1440. Default is 60.',
    'lblRotateKey'          => 'Rotate JWT signing key',
    'hintRotateKey'         => 'Will invalidate all active sessions and restart the service.',

    'sectionLicense'        => 'License',
    'licenseKeyStored'      => 'A Pro key is stored (not shown for security).',
    'lblLicenseKey'         => 'License key',

    'btnSave'               => 'Save',
    'btnCancel'             => 'Cancel',
    'msgConfigSaved'        => 'Configuration saved. The service has been restarted if needed.',
    'errLifetimeRange'      => 'Lifetime must be between 5 and 1440 minutes.',
];
