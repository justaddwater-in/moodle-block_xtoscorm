<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * English language pack for block_xtoscorm
 *
 * @package    block_xtoscorm
 * @category   string
 * @copyright  2026 Justaddwater <contact@justaddwater.in>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
$string['account'] = 'Account';
$string['apierror'] = 'Conversion failed: {$a}';
$string['apierror_auth'] = 'API error: {$a}';
$string['blockdescription'] = 'Convert your learning content into SCORM-compliant LMS packages instantly.';
$string['cachedef_xtoscorm_token'] = 'XtoScorm session token cache';
$string['completionmarker'] = 'Completion Marker';
$string['completionmarker_help'] = 'User must view the selected number of pages to mark completion.';
$string['completionmarker_video'] = 'Completion Marker';
$string['completionmarker_video_help'] = 'User must watch the selected percentage of the video to mark completion.';
$string['completionmarkerppt'] = 'Completion Marker';
$string['completionmarkerppt_help'] = 'User must view the selected number of slides to mark completion.';
$string['configerror'] = 'Plugin configuration error.';
$string['connectaccount'] = 'Connect Your XtoSCORM Account';
$string['connectwithxtoscorm'] = 'Connect with XtoSCORM';
$string['conversionfailed'] = 'Conversion Failed';
$string['convertbtn'] = 'Convert to SCORM';

$string['convertpdflink'] = 'PDF to SCORM';
$string['convertppt'] = 'Convert PPT to SCORM';
$string['convertpptlink'] = 'PPT to SCORM';
$string['convertvideo'] = 'Convert Video to SCORM';
$string['convertvideolink'] = 'VIDEO to SCORM';
$string['decryptfaileddebug'] = 'Failed to decrypt token for user {$a}';
$string['downloadfailed'] = "Download Failed";
$string['email'] = 'Email';
$string['errorreadingpdf'] = "Error reading PDF";
$string['expiredstate'] = 'Authentication request expired.';
$string['filenotselected'] = 'Please select a file before converting.';
$string['filereaderror'] = 'File read error';
$string['fitpdfto'] = 'Fit PDF To';
$string['height'] = 'Height';
$string['hide'] = 'Hide';
$string['invalidresponse'] = 'Invalid API response';
$string['invalidsession'] = 'Invalid session received from authentication server';
$string['invalidsessionid'] = 'Invalid session ID.';
$string['invalidstate'] = 'Invalid authentication state.';
$string['invalidtype'] = 'Invalid File Type';
$string['limitreached'] = 'Your conversion limit has been reached. Please login to continue.';
$string['loading'] = 'Loading..';
$string['loggedinuser'] = 'Logged In User';
$string['loginsuccess'] = 'Login successful.';
$string['logout'] = 'Logout';
$string['name'] = 'Name';
$string['nodownloadurl'] = 'No download URL received';
$string['nofilefound'] = 'No File Found';
$string['notoken'] = 'Access token missing from authentication response: {$a}';
$string['pagebypage'] = 'Page by Page';
$string['pdftoscorm'] = 'PDF to SCORM';
$string['pdftoscormconverter'] = 'PDF to SCORM Converter';
$string['pdfviewmode'] = 'PDF View Mode';
$string['pleaselogin'] = 'Please Login';
$string['pluginname'] = 'XtoSCORM';
$string['ppttoscorm'] = 'PPT to SCORM';
$string['ppttoscormconverter'] = 'PPT to SCORM Converter';
$string['privacy:metadata:auth'] =
    'The plugin connects to an external authentication provider for account login.';
$string['privacy:metadata:auth:email'] =
    'The user email shared with the authentication provider.';
$string['privacy:metadata:auth:fullname'] =
    'The user full name shared with the authentication provider.';
$string['privacy:metadata:auth:userid'] =
    'The Moodle user ID shared with the authentication provider.';
$string['privacy:metadata:block_xtoscorm_tokens'] = 'Stores OAuth tokens for XtoSCORM integration.';
$string['privacy:metadata:block_xtoscorm_tokens:expiresat'] = 'Token expiry timestamp.';
$string['privacy:metadata:block_xtoscorm_tokens:refreshtoken'] = 'Encrypted refresh token (not exported for security).';
$string['privacy:metadata:block_xtoscorm_tokens:timecreated'] = 'Record creation timestamp.';
$string['privacy:metadata:block_xtoscorm_tokens:token'] = 'Encrypted access token (not exported for security).';
$string['privacy:metadata:block_xtoscorm_tokens:userid'] = 'The ID of the user.';
$string['privacy:metadata:external'] =
    'The plugin sends user data to the external XtoSCORM conversion service.';
$string['privacy:metadata:external:conversiontype'] =
    'Type of SCORM conversion requested by the user.';
$string['privacy:metadata:external:deviceinfo'] =
    'Device and browser information sent to the external conversion service.';
$string['privacy:metadata:external:email'] =
    'The user email address sent to the external conversion service.';
$string['privacy:metadata:external:filename'] =
    'Uploaded file names processed by the conversion service.';
$string['privacy:metadata:external:fullname'] =
    'The user full name sent to the external conversion service.';
$string['privacy:metadata:external:ipaddress'] =
    'The user IP address sent to the external conversion service.';
$string['privacy:metadata:external:userid'] =
    'The Moodle user ID sent to the external conversion service.';
$string['processing'] = 'Processing...';
$string['scorm12'] = 'SCORM 1.2';
$string['scorm2004'] = 'SCORM 2004';
$string['scormversion'] = 'SCORM Version';
$string['scormversionvideo'] = 'SCORM Version';
$string['sequentialscroll'] = 'Sequential (Scroll)';
$string['sessionexpired'] = 'Your session has expired. Please reconnect your account.';
$string['show'] = 'Show';
$string['tokenexpireddebug'] = 'Token expired for user {$a}';
$string['totalpages'] = "Total Pages:";
$string['uploadpdf'] = 'Upload PDF (Max 10MB)';
$string['uploadpdfplaceholder'] = 'Upload PDF to enable';
$string['uploadppt'] = 'Upload PPT (Max 10MB)';
$string['uploadvideo'] = 'Upload Video (Max 50MB)';
$string['videoseekbar'] = 'Video Seek Bar';
$string['videotoscorm'] = 'Video to SCORM';
$string['videotoscormconverter'] = 'PPT to SCORM Converter';
$string['width'] = 'Width';
$string['xtoscorm:addinstance'] = 'Add a new XtoSCORM block';
$string['xtoscorm:myaddinstance'] = 'Add a new XtoSCORM block to Dashboard';
$string['xtoscorm:use'] = 'Use XtoSCORM conversion features';
