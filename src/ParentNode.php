<?php
namespace Pharborist;

/**
 * A node that has children.
 */
abstract class ParentNode extends Node implements ParentNodeInterface {
  /**
   * @var Node
   */
  protected $head;

  /**
   * @var Node
   */
  protected $tail;

  /**
   * @var int
   */
  protected $childCount;

  protected function getProperties() {
    $properties = get_object_vars($this);
    unset($properties['head']);
    unset($properties['tail']);
    unset($properties['childCount']);
    unset($properties['parent']);
    unset($properties['previous']);
    unset($properties['next']);
    return $properties;
  }

  public function childCount() {
    return $this->childCount;
  }

  public function firstChild() {
    return $this->head;
  }

  public function lastChild() {
    return $this->tail;
  }

  /**
   * Get children that are instance of class.
   * @param string $class_name
   * @return Node[]
   */
  protected function childrenByInstance($class_name) {
    $matches = [];
    $child = $this->head;
    while ($child) {
      if ($child instanceof $class_name) {
        $matches[] = $child;
      }
      $child = $child->next;
    }
    return $matches;
  }

  public function children(callable $callback = NULL) {
    $matches = [];
    $child = $this->head;
    while ($child) {
      if ($callback === NULL || $callback($child)) {
        $matches[] = $child;
      }
      $child = $child->next;
    }
    return new NodeCollection($matches);
  }

  /**
   * Prepend a child to node.
   * @param Node $node
   * @return $this
   */
  protected function prependChild(Node $node) {
    if ($this->head === NULL) {
      $this->childCount++;
      $node->parent = $this;
      $node->previous = NULL;
      $node->next = NULL;
      $this->head = $this->tail = $node;
    }
    else {
      $this->insertBeforeChild($this->head, $node);
      $this->head = $node;
    }
    return $this;
  }

  public function prepend($nodes) {
    if ($nodes instanceof Node) {
      $this->prependChild($nodes);
    }
    elseif ($nodes instanceof NodeCollection) {
      foreach ($nodes->reverse() as $node) {
        $this->prependChild($node);
      }
    }
    elseif (is_array($nodes)) {
      foreach (array_reverse($nodes) as $node) {
        $this->prependChild($node);
      }
    }
    else {
      throw new \InvalidArgumentException();
    }
    return $this;
  }

  /**
   * Append a child to node.
   * @param Node $node
   * @return $this
   */
  protected function appendChild(Node $node) {
    if ($this->tail === NULL) {
      $this->prependChild($node);
    }
    else {
      $this->insertAfterChild($this->tail, $node);
      $this->tail = $node;
    }
    return $this;
  }

  /**
   * Add a child to node.
   *
   * Internal use only, used by parser when building a node.
   *
   * @param Node $node
   * @param string $property_name
   * @return $this
   */
  public function addChild(Node $node, $property_name = NULL) {
    $this->appendChild($node);
    if ($property_name !== NULL) {
      $this->{$property_name} = $node;
    }
    return $this;
  }

  /**
   * Add children to node.
   *
   * Internal use only, used by parser when building a node.
   *
   * @param Node[] $children
   */
  public function addChildren(array $children) {
    foreach ($children as $child) {
      $this->appendChild($child);
    }
  }

  /**
   * Merge another parent node into this node.
   * @param ParentNode $node
   */
  public function mergeNode(ParentNode $node) {
    $child = $node->head;
    while ($child) {
      $next = $child->next;
      $this->appendChild($child);
      $child = $next;
    }
    foreach ($node->getProperties() as $name => $value) {
      $this->{$name} = $value;
    }
  }

  public function append($nodes) {
    if ($nodes instanceof Node) {
      $this->appendChild($nodes);
    }
    elseif ($nodes instanceof NodeCollection || is_array($nodes)) {
      foreach ($nodes as $node) {
        $this->appendChild($node);
      }
    }
    else {
      throw new \InvalidArgumentException();
    }
    return $this;
  }

  /**
   * Insert a node before a child.
   * @param Node $child
   * @param Node $node
   * @return $this
   */
  protected function insertBeforeChild(Node $child, Node $node) {
    $this->childCount++;
    $node->parent = $this;
    if ($child->previous === NULL) {
      $this->head = $node;
    }
    else {
      $child->previous->next = $node;
    }
    $node->previous = $child->previous;
    $node->next = $child;
    $child->previous = $node;
    return $this;
  }

  /**
   * Insert a node after a child.
   * @param Node $child
   * @param Node $node
   * @return $this
   */
  protected function insertAfterChild(Node $child, Node $node) {
    $this->childCount++;
    $node->parent = $this;
    if ($child->next === NULL) {
      $this->tail = $node;
    }
    else {
      $child->next->previous = $node;
    }
    $node->previous = $child;
    $node->next = $child->next;
    $child->next = $node;
    return $this;
  }

  /**
   * Remove a child node.
   * @param Node $child
   * @return $this
   */
  protected function removeChild(Node $child) {
    $this->childCount--;
    foreach ($this->getProperties() as $name => $value) {
      if ($child === $value) {
        $this->{$name} = NULL;
        break;
      }
    }
    if ($child->previous === NULL) {
      $this->head = $child->next;
    }
    else {
      $child->previous->next = $child->next;
    }
    if ($child->next === NULL) {
      $this->tail = $child->previous;
    }
    else {
      $child->next->previous = $child->previous;
    }
    $child->parent = NULL;
    $child->previous = NULL;
    $child->next = NULL;
    return $this;
  }

  /**
   * Replace a child node.
   * @param Node $child
   * @param Node $replacement
   * @return $this
   */
  protected function replaceChild(Node $child, Node $replacement) {
    foreach ($this->getProperties() as $name => $value) {
      if ($child === $value) {
        $this->{$name} = $replacement;
        break;
      }
    }
    $replacement->parent = $this;
    $replacement->previous = $child->previous;
    $replacement->next = $child->next;
    if ($child->previous === NULL) {
      $this->head = $replacement;
    }
    else {
      $child->previous->next = $replacement;
    }
    if ($child->next === NULL) {
      $this->tail = $replacement;
    }
    else {
      $child->next->previous = $replacement;
    }
    $child->parent = NULL;
    $child->previous = NULL;
    $child->next = NULL;
    return $this;
  }

  /**
   * {@inheritDoc}
   * @return TokenNode
   */
  public function firstToken() {
    $head = $this->head;
    while ($head instanceof ParentNode) {
      $head = $head->head;
    }
    return $head;
  }

  /**
   * {@inheritDoc}
   * @return TokenNode
   */
  public function lastToken() {
    $tail = $this->tail;
    while ($tail instanceof ParentNode) {
      $tail = $tail->tail;
    }
    return $tail;
  }

  public function has(callable $callback) {
    $child = $this->head;
    while ($child) {
      if ($child instanceof ParentNode && $child->has($callback)) {
        return TRUE;
      }
      elseif ($callback($child)) {
        return TRUE;
      }
      $child = $child->next;
    }
    return FALSE;
  }

  public function find(callable $callback) {
    $matches = [];
    $child = $this->head;
    while ($child) {
      if ($callback($child)) {
        $matches[] = $child;
      }
      if ($child instanceof ParentNode) {
        $matches = array_merge($matches, $child->find($callback)->toArray());
      }
      $child = $child->next;
    }
    return new NodeCollection($matches);
  }

  public function getSourcePosition() {
    if ($this->head === NULL) {
      return $this->parent->getSourcePosition();
    }
    $child = $this->head;
    return $child->getSourcePosition();
  }

  public function __clone() {
    // Clone does not belong to a parent.
    $this->parent = NULL;
    $this->previous = NULL;
    $this->next = NULL;
    list($this->head, $properties) = unserialize(serialize([$this->head, $this->getProperties()]));
    foreach ($properties as $name => $value) {
      $this->{$name} = $value;
    }
  }

  public function getText() {
    $str = '';
    $child = $this->head;
    while ($child) {
      $str .= (string) $child;
      $child = $child->next;
    }
    return $str;
  }

  public function __toString() {
    return $this->getText();
  }
}
