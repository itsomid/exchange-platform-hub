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
    const pageIsHttps = window.location.protocol === "https:";
    const isTls = pageIsHttps || scheme === "https";
    const wsHost =
        runtimeConfig.host ||
        import.meta.env.VITE_REVERB_HOST ||
        import.meta.env.VITE_PUSHER_HOST ||
        window.location.hostname;
    const configuredPort = Number(
        runtimeConfig.port ??
            import.meta.env.VITE_REVERB_PORT ??
            import.meta.env.VITE_PUSHER_PORT ??
            (isTls ? 443 : 80),
    );
    const wsPort = configuredPort || (isTls ? 443 : 80);
    const wssPort =
        Number(runtimeConfig.wssPort) ||
        (isTls ? (wsPort === 80 ? 443 : wsPort) : 443);
    const wsPath = runtimeConfig.wsPath ?? "";

    window.Echo = new Echo({
        broadcaster: "reverb",
        key,
        wsHost,
        wsPort,
        wssPort,
        wsPath,
        forceTLS: isTls,
        enabledTransports: isTls ? ["wss"] : ["ws", "wss"],
    });
}
