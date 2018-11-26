<?php
namespace p7g\EventEmitter;

// TODO: https://nodejs.org/api/events.html#events_class_eventemitter

abstract class EventEmitter {
  public const NEW_LISTENER = 'newListener';
  public const REMOVE_LISTENER = 'removeListener';

  /** @var int $defaultMaxListeners */
  public static $defaultMaxListeners = 10; // TODO: make this do something

  /** @var int $maxListeners */
  private $maxListeners;

  /** @var \Closure[][] $observers */
  private $observers = [];

  /** @var \Closure[][] $observersOnce */
  private $observersOnce = [];

  public function on(string $event, \Closure $handler): self {
    $this->emit(self::NEW_LISTENER, $event, $handler);
    $this->observers[$event][] = $handler;
    return $this;
  }

  public function once(string $event, \Closure $handler): self {
    $this->emit(self::NEW_LISTENER, $event, $handler);
    $this->observersOnce[$event][] = $handler;
    return $this;
  }

  public function removeListener(string $eventName, callable $handler): self {
    if (!empty($this->observers[$eventName])) {
      array_remove($this->observers[$eventName], $handler);
    }
    if (!empty ($this->observersOnce[$eventName])) {
      array_remove($this->observersOnce[$eventName], $handler);
    }
    return $this;
  }

  public function off(string $eventName, callable $handler): self {
    return $this->removeListener($eventName, $handler);
  }

  public function removeAllListeners(string $eventName = null): self {
    if ($eventName === null) {
      $this->observers = [];
      $this->observersOnce = [];
    }
    else {
      unset($this->observers[$eventName]);
      unset($this->observersOnce[$eventName]);
    }
    return $this;
  }

  public function emit(string $event, ...$data): bool {
    $listeners = $this->observers[$event] ?? null;
    $listenersOnce = $this->observersOnce[$event] ?? null;
    $hasListeners = !empty($listeners);
    $hasListenersOnce = !empty($listenersOnce);
    $noListeners = !$hasListeners && !$hasListenersOnce;

    if ($event === 'error' && $noListeners) {
      // FIXME: don't use generic exception
      throw $data[0] ?? (new \Exception('Uncaught event error'));
    }

    foreach ($this->observers[$event] ?? [] as $observer) {
      $observer(...$data);
    }

    foreach ($this->observersOnce[$event] ?? [] as $i => $observer) {
      $fn = &$observer;
      unset($this->observersOnce[$event][$i]);
      $fn(...$data);
    }

    return !$noListeners;
  }

  public function getMaxListeners(): int {
    if (isset($this->maxListeners)) {
      return $this->maxListeners;
    }
    return self::$defaultMaxListeners;
  }

  public function setMaxListeners(int $n): self {
    $this->maxListeners = $n;
    return $this;
  }

  public function eventNames(): array {
    return array_merge(
      array_keys($this->observers),
      array_keys($this->observersOnce)
    );
  }

  public function listenerCount(string $event): int {
    $count = count($this->observers[$event] ?? []);
    $countOnce = count($this->observersOnce[$event] ?? []);
    return $count + $countOnce;
  }
}

function array_remove(array &$array, $item): void {
  $position = array_search($item, $array);
  if ($position !== false) {
    unset($array[$position]);
  }
}
