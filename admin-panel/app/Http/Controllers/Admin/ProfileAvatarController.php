<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Currency;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProfileAvatarController extends Controller
{
    /** DiceBear styles offered in the "online" tab. Only this server talks to DiceBear, never the admin's browser. */
    public const STYLES = [
        'bottts' => 'ربات',
        'pixel-art' => 'پیکسلی',
        'adventurer' => 'ماجراجو',
        'avataaars' => 'کارتونی',
        'lorelei' => 'لورلی',
        'fun-emoji' => 'ایموجی',
        'thumbs' => 'شست',
    ];

    private const DICEBEAR_URL = 'https://api.dicebear.com/9.x/%s/png';

    private const AVATAR_SIZE = 256;

    private const PREVIEW_SIZE = 128;

    private const MAX_PIXELS = 25_000_000;

    /** Pick one of the bundled avatars or a currency logo. */
    public function update(Request $request): JsonResponse
    {
        $avatar = $request->validate(['avatar' => ['required', 'string', 'max:200']])['avatar'];

        $isBundled = in_array($avatar, Admin::availableAvatars(), true);
        $isCoin = str_starts_with($avatar, Admin::COIN_AVATAR_PREFIX)
            && $this->coinLogos()->contains('logo', substr($avatar, strlen(Admin::COIN_AVATAR_PREFIX)));

        if (! $isBundled && ! $isCoin) {
            throw ValidationException::withMessages(['avatar' => 'آواتار انتخاب‌شده معتبر نیست.']);
        }

        return $this->save(Auth::guard('admin')->user(), $avatar);
    }

    /** Currency logos usable as avatars (loaded lazily when the tab is opened). */
    public function coins(): JsonResponse
    {
        return response()->json([
            'coins' => $this->coinLogos()->map(fn ($coin) => [
                'key' => Admin::COIN_AVATAR_PREFIX.$coin['logo'],
                'symbol' => $coin['symbol'],
                'url' => Currency::logoUrl($coin['logo']),
            ])->values(),
        ]);
    }

    /** Server-side proxy + cache of a DiceBear preview, so the browser only loads images from our own domain. */
    public function preview(Request $request): Response
    {
        [$style, $seed] = $this->styleAndSeed($request);

        $png = $this->fetchDicebear($style, $seed, self::PREVIEW_SIZE);
        abort_if($png === null, 502);

        return response($png, 200, ['Content-Type' => 'image/png', 'Cache-Control' => 'private, max-age=86400']);
    }

    /** Save a DiceBear avatar as a local file. */
    public function generate(Request $request): JsonResponse
    {
        [$style, $seed] = $this->styleAndSeed($request);

        $png = $this->fetchDicebear($style, $seed, self::AVATAR_SIZE);
        $normalized = $png === null ? null : $this->squarePng($png);

        if ($normalized === null) {
            return response()->json(['success' => false, 'message' => 'دریافت آواتار از سرویس آنلاین ممکن نشد. لطفاً دوباره تلاش کنید.'], 502);
        }

        return $this->save(Auth::guard('admin')->user(), $this->storeCustomFile(Auth::guard('admin')->user(), $normalized));
    }

    /** Upload a personal photo; it is re-encoded as a square PNG, so metadata and non-image payloads never reach disk. */
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'photo' => ['required', 'file', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
        ], [
            'photo.required' => 'یک عکس انتخاب کنید.',
            'photo.mimes' => 'فرمت عکس باید PNG، JPG یا WebP باشد.',
            'photo.max' => 'حجم عکس نباید بیشتر از ۲ مگابایت باشد.',
            'photo.uploaded' => 'آپلود عکس ناموفق بود.',
        ]);

        $normalized = $this->squarePng(file_get_contents($request->file('photo')->getRealPath()));

        if ($normalized === null) {
            throw ValidationException::withMessages(['photo' => 'این فایل عکس معتبری نیست.']);
        }

        $admin = Auth::guard('admin')->user();

        return $this->save($admin, $this->storeCustomFile($admin, $normalized));
    }

    private function save(Admin $admin, string $value): JsonResponse
    {
        $previous = $admin->avatar;

        $admin->forceFill(['avatar' => $value])->save();

        if ($previous !== $value && Admin::isCustomAvatarPath($previous)) {
            Storage::disk('public')->delete($previous);
        }

        return response()->json([
            'success' => true,
            'message' => 'آواتار شما با موفقیت تغییر کرد.',
            'avatar' => $admin->avatar(),
            'key' => $admin->avatarSelection(),
        ]);
    }

    private function storeCustomFile(Admin $admin, string $png): string
    {
        $path = 'avatars/custom/'.$admin->id.'-'.Str::lower(Str::random(12)).'.png';
        Storage::disk('public')->put($path, $png);

        return $path;
    }

    /** @return Collection<int, array{symbol: string, logo: string}> */
    private function coinLogos(): Collection
    {
        return Currency::query()
            ->whereNotNull('logo')->where('logo', '!=', '')
            ->orderBy('symbol')
            ->get(['symbol', 'logo'])
            ->unique('logo')
            ->filter(fn ($currency) => Currency::logoExists($currency->logo))
            ->map(fn ($currency) => ['symbol' => $currency->symbol, 'logo' => $currency->logo])
            ->values();
    }

    /** @return array{0: string, 1: string} */
    private function styleAndSeed(Request $request): array
    {
        $data = $request->validate([
            'style' => ['required', Rule::in(array_keys(self::STYLES))],
            'seed' => ['required', 'regex:/^[A-Za-z0-9]{4,24}$/'],
        ]);

        return [$data['style'], $data['seed']];
    }

    private function fetchDicebear(string $style, string $seed, int $size): ?string
    {
        $cacheKey = "avatar_dicebear:{$style}:{$seed}:{$size}";

        if ($cached = Cache::get($cacheKey)) {
            return base64_decode($cached);
        }

        try {
            $response = Http::timeout(8)->get(sprintf(self::DICEBEAR_URL, $style), ['seed' => $seed, 'size' => $size]);
        } catch (\Throwable) {
            return null;
        }

        $body = $response->body();
        if (! $response->successful() || ! str_starts_with($body, "\x89PNG")) {
            return null;
        }

        Cache::put($cacheKey, base64_encode($body), now()->addDay());

        return $body;
    }

    /** Decode any raster image with GD, center-crop it to a square and re-encode it as a 256px PNG. */
    private function squarePng(string $binary): ?string
    {
        $info = @getimagesizefromstring($binary);
        if (! $info || $info[0] * $info[1] > self::MAX_PIXELS) {
            return null;
        }

        $source = @imagecreatefromstring($binary);
        if (! $source) {
            return null;
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $side = min($width, $height);

        $target = imagecreatetruecolor(self::AVATAR_SIZE, self::AVATAR_SIZE);
        imagealphablending($target, false);
        imagesavealpha($target, true);
        imagefill($target, 0, 0, imagecolorallocatealpha($target, 0, 0, 0, 127));
        imagecopyresampled(
            $target, $source, 0, 0, intdiv($width - $side, 2), intdiv($height - $side, 2),
            self::AVATAR_SIZE, self::AVATAR_SIZE, $side, $side
        );

        ob_start();
        imagepng($target);

        return ob_get_clean() ?: null;
    }
}
