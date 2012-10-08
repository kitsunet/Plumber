<?php
namespace Sandstorm\Plumber;

/*                                                                        *
 * This script belongs to the FLOW3 package "Sandstorm.Plumber".          *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3          *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Http\Request;
use TYPO3\Flow\Http\Response;
use TYPO3\Flow\Error\Message;

/**
 * A request handler which can handle HTTP requests.
 *
 * @Flow\Scope("singleton")
 */
class RequestHandler extends \TYPO3\Flow\Http\RequestHandler {

	/**
	 * @var \TYPO3\Flow\Http\Response
	 */
	protected $response;

	/**
	 * This request handler can handle any web request.
	 *
	 * @return boolean If the request is a web request, TRUE otherwise FALSE
	 */
	public function canHandleRequest() {
		return (PHP_SAPI !== 'cli' && ((strlen($_SERVER['REQUEST_URI']) === 9 && $_SERVER['REQUEST_URI'] === '/profiler') || in_array(substr($_SERVER['REQUEST_URI'], 0, 10), array('/profiler/', '/profiler?'))));
	}

	/**
	 * Returns the priority - how eager the handler is to actually handle the
	 * request.
	 *
	 * @return integer The priority of the request handler.
	 */
	public function getPriority() {
		return 200;
	}

	/**
	 * Handles a HTTP request
	 *
	 * @return void
	 */
	public function handleRequest() {
			// Create the request very early so the Resource Management has a chance to grab it:
		$this->request = Request::createFromEnvironment();
		$this->response = new Response();

		$this->boot();
		$this->resolveDependencies();
		$this->request->injectSettings($this->settings);

		$packageManager = $this->bootstrap->getEarlyInstance('TYPO3\Flow\Package\PackageManagerInterface');
		$configurationSource = $this->bootstrap->getObjectManager()->get('TYPO3\Flow\Configuration\Source\YamlSource');

		$this->router->setRoutesConfiguration($configurationSource->load($packageManager->getPackage('Sandstorm.Plumber')->getConfigurationPath() . \TYPO3\Flow\Configuration\ConfigurationManager::CONFIGURATION_TYPE_ROUTES));
		$actionRequest = $this->router->route($this->request);

		$this->securityContext->setRequest($actionRequest);

		$this->dispatcher->dispatch($actionRequest, $this->response);

		$this->response->makeStandardsCompliant($this->request);
		$this->response->send();

		$this->bootstrap->shutdown('Runtime');
		$this->exit->__invoke();
	}
}
?>