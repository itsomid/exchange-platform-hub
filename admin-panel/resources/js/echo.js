import Echo from "laravel-echo";
import Pusher from "pusher-js";

window.Pusher = Pusher;

const key =
    import.meta.env.VITE_REVERB_APP_KEY ?? import.meta.env.VITE_PUSHER_APP_KEY;

if (key) {
    const scheme =
        import.meta.env.VITE_REVERB_SCHEME ??
        import.meta.env.VITE_PUSHER_SCHEME ??
        "http";
    const wsHost =
        import.meta.env.VITE_REVERB_HOST ??
        import.meta.env.VITE_PUSHER_HOST ??
        window.location.hostname;
    const wsPort = Number(
        import.meta.env.VITE_REVERB_PORT ??
            import.meta.env.VITE_PUSHER_PORT ??
            80,
    );
    const isTls = scheme === "https";

    window.Echo = new Echo({
        broadcaster: "reverb",
        key,
        wsHost,
        wsPort,
        wssPort: wsPort,
        forceTLS: isTls,
        enabledTransports: ["ws", "wss"],
    });
}
