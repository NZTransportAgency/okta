# Okta Integration

Okta provides secure identity management and single sign-on to the application, whether in the cloud, on-premises or on a mobile device for every employee.

# Update the following environment variables in _ss_environment file 
URL for generating the certificates - https://developers.onelogin.com/saml/online-tools/x509-certs/obtain-self-signed-certs
These certificates can be defined with any name you wish and store the certificates anywhere in the server/local storage.  

| Variable | Value/description |
| ------ | ------ |
| SS_OKTA_SP_ISSUER | https://yourdomain.co.nz  - your domain name |
| SS_OKTA_SP_LOGIN_URL | https://yourdomain.co.nz/okta/sso  |
| SS_OKTA_SP_LOGOUT_URL | https://yourdomain.co.nz/okta/slo |
| SS_OKTA_SP_X509 | file_get_contents('/var/www/certs/org.cer') - path for generated "CSR" key   |
| SS_OKTA_SP_PEM | file_get_contents('/var/www/certs/org.pem') - path for generated "private" key |
| SS_OKTA_IDP_X509CERT | file_get_contents('/var/www/certs/org-okta.crt') - download certificate from Okta |
| SS_OKTA_IDP_ISSUER | http://okta.com/XYZ123 |
| SS_OKTA_IDP_LOGIN_URL | https://org.okta.com/app/appname/XYZ123/sso/saml |
| SS_OKTA_IDP_LOGOUT_URL | https://org.okta.com/app/appname/XYZ123/sso/saml |
| SS_OKTA_IP_WHITELIST | if you do not want to force login through Okta add your ip address, e.g. 127.0.0.1,192.168.0.10 |

You can download the SS_OKTA_IDP_X509CERT and  SS_OKTA_IDP_ISSUER,SS_OKTA_IDP_LOGIN_URL, SS_OKTA_IDP_LOGOUT_URL 
information from okta i.e https://{org}.okta.com/app/{appname}/{XYZ123}/setup/help/SAML_2_0/instructions where:

org: organization name
appname: application name which is define by okta
XYZ123: random app id which is define by okta

Alternatively:

1. Login to your okta account
1. Click the admin button
3. Click applications in applications menu
4. Click your application from list of applications
5. Click on the 'Sign On' tab
6. Click on the 'view setup introduction' button

You will see the details and the download option for the certificate.

# Setup SAML settings in okta

1. Login to your Okta account and follow the last steps to go to your application
2. Click 'General' tab
3. Click 'Edit' button in SAML settings
4. Click 'Next'
5. Click 'Show Advanced Settings'

Update the following settings

| Field | Value |
| ------ | ------ |
| Single sign on URL | https://yourdomian.co.nz/okta/ssl |
| Audience URI (SP Entity ID) | https://yourdomain.co.nz  |
| Name ID format | unspecified|
| Application username | Email   |
| Response | Signed |
| Assertion Signature  | Signed |
| Signature Algorithm | RSA-SHA256 |
| Digest Algorithm | SHA256 |
| Assertion Encryption  | Encrypted |
| Encryption Algorithm | AES256-CBC |
| Key Transport Algorithm | RSA-OAEP |
| Encryption Certificate | upload the X.509 cert which is generated from https://developers.onelogin.com/saml/online-tools/x509-certs/obtain-self-signed-certs |

### Setup Attribute statements

| Name | Value |
| ------ | ------ |
| FirstName | user.firstName |
| Surname | user.lastname  |
| Email | user.email  |
| Login | user.login  |
| SID | user.uniqueID  |

# Assign people/accounts in okta
login to your okta account and follow the last steps to go to your application, then add people/accounts from the people section to give access to the application.

# Install onelogin saml module
In order to import the saml onelogin to your current php project, add this to your `composer.json` file inside the "require" section

The original onelogin/saml module initially brought on issues upon signing out. Therefore, the purpose behind this fork is to resolve any issues that the original module brought on and ensure that no further issues arise.

```javascript
    "onelogin/php-saml": "dev-sign-logout-request"
```

Add this to the "repositories" section

```javascript
{
    "type": "vcs",
    "url": "git@github.com:micmania1/php-saml.git"
}
```

Lastly, run `composer install`
