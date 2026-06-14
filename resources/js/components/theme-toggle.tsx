import { type Appearance, useAppearance } from '@/hooks/use-appearance';
import { Monitor, Moon, Sun } from 'lucide-react';

const MODES: { value: Appearance; label: string; Icon: typeof Sun }[] = [
    { value: 'light', label: 'Light', Icon: Sun },
    { value: 'dark', label: 'Dark', Icon: Moon },
    { value: 'system', label: 'System', Icon: Monitor },
];

/** A compact light / dark / system theme toggle as sun / moon / screen icons. */
export function ThemeToggle() {
    const { appearance, updateAppearance } = useAppearance();
    return (
        <div className="border-border flex items-center gap-1 rounded-md border p-0.5" role="group" aria-label="Theme">
            {MODES.map(({ value, label, Icon }) => (
                <button
                    key={value}
                    type="button"
                    aria-label={label}
                    aria-pressed={appearance === value}
                    title={label}
                    onClick={() => updateAppearance(value)}
                    className={`focus-visible:ring-ring rounded p-1.5 transition-colors focus-visible:ring-2 focus-visible:outline-none ${
                        appearance === value ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:text-foreground'
                    }`}
                >
                    <Icon className="size-4" aria-hidden="true" />
                </button>
            ))}
        </div>
    );
}
