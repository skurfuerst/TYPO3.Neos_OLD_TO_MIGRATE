<?php
namespace TYPO3\Neos\EventLog\Domain\Model;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Persistence\PersistenceManagerInterface;
use TYPO3\Flow\Utility\Arrays;
use TYPO3\Neos\Domain\Repository\SiteRepository;
use TYPO3\Neos\Domain\Service\ContentContext;
use TYPO3\Neos\Service\UserService;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface;

/**
 * A specific event which is used for TYPO3CR Nodes (i.e. content).
 *
 * @Flow\Entity
 * @ORM\InheritanceType("SINGLE_TABLE")
 * The following annotation is not correctly picked up so doctrine migrations would never create this index. It is still contained in the migration.
 * @ORM\Table(
 *    indexes={
 *		@ORM\Index(name="documentnodeidentifier", columns={"documentnodeidentifier"})
 *    }
 * )
 */
class NodeEvent extends Event {

	/**
	 * the node identifier which was created/modified/...
	 *
	 * @var string
	 */
	protected $nodeIdentifier;

	/**
	 * the document node identifier on which the action took place. is equal to NodeIdentifier if the action happened on documentNodes
	 *
	 * @var string
	 */
	protected $documentNodeIdentifier;

	/**
	 * the workspace name where the action took place
	 *
	 * @var string
	 */
	protected $workspaceName;

	/**
	 * the dimension values for that event
	 *
	 * @var array
	 */
	protected $dimension;

	/**
	 * @Flow\Inject
	 * @var UserService
	 */
	protected $userService;

	/**
	 * @Flow\Inject
	 * @var ContextFactoryInterface
	 */
	protected $contextFactory;

	/**
	 * @Flow\Inject
	 * @var PersistenceManagerInterface
	 */
	protected $persistenceManager;

	/**
	 * @Flow\Inject
	 * @var SiteRepository
	 */
	protected $siteRepository;

	/**
	 * Return name of the workspace where the node event happened
	 *
	 * @return string
	 */
	public function getWorkspaceName() {
		return $this->workspaceName;
	}

	public function isDocumentEvent() {
		return $this->documentNodeIdentifier === $this->nodeIdentifier;
	}

	/**
	 * Return the node identifier of the closest parent document node related to this event
	 *
	 * @return string
	 */
	public function getDocumentNodeIdentifier() {
		return $this->documentNodeIdentifier;
	}

	/**
	 * Return the node identifier of the node this event relates to
	 *
	 * @return string
	 */
	public function getNodeIdentifier() {
		return $this->nodeIdentifier;
	}

	/**
	 * Set the "context node" this operation was working on.
	 *
	 * @param NodeInterface $node
	 * @return void
	 */
	public function setNode(NodeInterface $node) {
		$this->nodeIdentifier = $node->getIdentifier();
		$this->workspaceName = $node->getContext()->getWorkspaceName();
		$this->dimension = $node->getContext()->getDimensions();

		$context = $node->getContext();
		if ($context instanceof ContentContext && $context->getCurrentSite() !== NULL) {
			$siteIdentifier = $this->persistenceManager->getIdentifierByObject($context->getCurrentSite());
		} else {
			$siteIdentifier = NULL;
		}
		$this->data = Arrays::arrayMergeRecursiveOverrule($this->data, array(
			'nodeContextPath' => $node->getContextPath(),
			'nodeLabel' => $node->getLabel(),
			'nodeType' => $node->getNodeType()->getName(),
			'site' => $siteIdentifier
		));

		$node = self::getClosestAggregateNode($node);

		if ($node !== NULL) {
			$this->documentNodeIdentifier = $node->getIdentifier();
			$this->data = Arrays::arrayMergeRecursiveOverrule($this->data, array(
				'documentNodeContextPath' => $node->getContextPath(),
				'documentNodeLabel' => $node->getLabel(),
				'documentNodeType' => $node->getNodeType()->getName()
			));
		}
	}

	/**
	 * Override the workspace name. *MUST* be called after setNode(), else it won't have an effect.
	 *
	 * @param string $workspaceName
	 * @return void
	 */
	public function setWorkspaceName($workspaceName) {
		$this->workspaceName = $workspaceName;
	}

	/**
	 * Returns the closest aggregate node of the given node
	 *
	 * @param NodeInterface $node
	 * @return NodeInterface
	 */
	public static function getClosestAggregateNode(NodeInterface $node) {
		while ($node !== NULL && !$node->getNodeType()->isAggregate()) {
			$node = $node->getParent();
		}
		return $node;
	}

	/**
	 * Returns the closest document node, if it can be resolved.
	 *
	 * It might happen that, if this event refers to a node contained in a site which is not available anymore,
	 * Doctrine's proxy class of the Site domain model will fail with an EntityNotFoundException. We catch this
	 * case and return NULL.
	 *
	 * @return NodeInterface
	 */
	public function getDocumentNode() {
		try {
			$context = $this->contextFactory->create(array(
				'workspaceName' => $this->userService->getUserWorkspace()->getName(),
				'dimensions' => $this->dimension,
				'currentSite' => $this->siteRepository->findByIdentifier($this->data['site']),
				'invisibleContentShown' => TRUE
			));
			return $context->getNodeByIdentifier($this->documentNodeIdentifier);
		} catch(EntityNotFoundException $e) {
			return NULL;
		}
	}

	/**
	 * Returns the node this even refers to, if it can be resolved.
	 *
	 * It might happen that, if this event refers to a node contained in a site which is not available anymore,
	 * Doctrine's proxy class of the Site domain model will fail with an EntityNotFoundException. We catch this
	 * case and return NULL.
	 *
	 * @return NodeInterface
	 */
	public function getNode() {
		try {
			$context = $this->contextFactory->create(array(
				'workspaceName' => $this->userService->getUserWorkspace()->getName(),
				'dimensions' => $this->dimension,
				'currentSite' => $this->siteRepository->findByIdentifier($this->data['site']),
				'invisibleContentShown' => TRUE
			));
			return $context->getNodeByIdentifier($this->nodeIdentifier);
		} catch(EntityNotFoundException $e) {
			return NULL;
		}
	}

	/**
	 * @return string
	 */
	public function __toString() {
		return sprintf('NodeEvent[%s, %s]', $this->eventType, $this->nodeIdentifier);
	}
}