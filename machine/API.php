<?php
/**
 * DevShift Engine v1.0.0
 * (c) 2020-present Sapir Shemer, DevShift (devshift.biz)
 * Released under the MIT License with the additions present in the LICENSE.md
 * file in the root folder of the DevShift Engine original release source-code
 * @author Sapir Shemer
 */

 require "APIShift.php";

 use APIShift\Core\Authorizer;
 use APIShift\Core\Status;
 use APIShift\Core\Configurations;

// Step 1: Validate request format
if (!isset($_GET["c"]) || !isset($_GET["m"])) Status::message(Status::ERROR, "Invalid Request, Method or Controller not set");
if (!Configurations::INSTALLED && $_GET["c"] != "Installer") Status::message(Status::NOT_INSTALLED, "Not Installed, can only call the Installer controller");

// Step 3: Authorize & run the request
Authorizer::authorizeAndRun($_GET["c"], $_GET["m"], $_POST);

// Step 4: Post message if controller didn't exit application
Status::respond();