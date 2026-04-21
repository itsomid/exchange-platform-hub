import Echo from "laravel-echo";
import Pusher from "pusher-js";

window.Pusher = Pusher;

// Prefer PHP-rendered runtime config (window.__reverbConfig) over Vite-baked build-time env vars.
// This ensures production works correctly without rebuilding assets on every env change.
const runtimeConfig = window.__reverbConfig ?? {};

const key =
    runtimeConfig.key ||
    import.meta.env.VITE_REVERB_APP_KEY ||
    import.meta.env.VITE_PUSHER_APP_KEY;

if (key) {
    const scheme =
        runtimeConfig.scheme ||
        import.meta.env.VITE_REVERB_SCHEME ||
        import.meta.env.VITE_PUSHER_SCHEME ||
        "http";
    const wsHost =
        runtimeConfig.host ||
        import.meta.env.VITE_REVERB_HOST ||
        import.meta.env.VITE_PUSHER_HOST ||
        window.location.hostname;
    const wsPort = Number(
        runtimeConfig.port ??
            import.meta.env.VITE_REVERB_PORT ??
            import.meta.env.VITE_PUSHER_PORT ??
            8080,
    );
    const wsPath = runtimeConfig.wsPath ?? "";
    const isTls = scheme === "https";

    window.Echo = new Echo({
        broadcaster: "reverb",
        key,
        wsHost,
        wsPort,
        wssPort: wsPort,
        wsPath,
        forceTLS: isTls,
        enabledTransports: ["ws", "wss"],
    });
}

