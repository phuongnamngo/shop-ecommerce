export const NOTIFICATION_CHANGED_EVENT = "watch-notification-changed";

export function emitNotificationChanged(): void {
  window.dispatchEvent(new Event(NOTIFICATION_CHANGED_EVENT));
}
