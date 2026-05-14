import { Input } from '@/components/ui/input';

type Props = {
    name: string;
    value: number | string;
    onChange: (value: string) => void;
    allowNegative?: boolean;
    placeholder?: string;
    id?: string;
    required?: boolean;
};

export function MoneyInput({
    name,
    value,
    onChange,
    allowNegative = false,
    placeholder = '0.00',
    id,
    required,
}: Props) {
    return (
        <Input
            id={id ?? name}
            name={name}
            type="number"
            step="0.01"
            min={allowNegative ? undefined : 0}
            value={value}
            onChange={(e) => onChange(e.target.value)}
            placeholder={placeholder}
            required={required}
            inputMode="decimal"
        />
    );
}
