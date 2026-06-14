import { Logo } from './logo';

/**
 * Brand mark. A thin wrapper around <Logo> (driven by config/branding.php) kept only
 * so the few legacy layouts that still import the old Laravel SVG (app-header,
 * auth-card/split) render the Open Courts logo instead — there is no Laravel mark
 * anywhere in the app. Only `className` is forwarded; the old SVG `fill`/`text-*`
 * classes are harmless no-ops on the underlying <img>.
 */
export default function AppLogoIcon({ className }: { className?: string }) {
    return <Logo className={className} />;
}
