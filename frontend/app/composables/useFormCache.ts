/**
 * Persists a form's state in localStorage under `form-cache-<key>`.
 * Data survives slide-over close/reopen until the user saves or clears.
 */
export function useFormCache<T extends object>(key: string, defaults: T) {
  const storageKey = `form-cache-${key}`

  function fresh(): T {
    return JSON.parse(JSON.stringify(defaults))
  }

  function load(): T {
    if (!import.meta.client) return fresh()
    try {
      const raw = localStorage.getItem(storageKey)
      return raw ? { ...fresh(), ...JSON.parse(raw) } : fresh()
    } catch {
      return fresh()
    }
  }

  function persist(val: T) {
    if (!import.meta.client) return
    try { localStorage.setItem(storageKey, JSON.stringify(val)) } catch {}
  }

  function clear() {
    if (!import.meta.client) return
    localStorage.removeItem(storageKey)
  }

  return { load, persist, clear, fresh }
}
