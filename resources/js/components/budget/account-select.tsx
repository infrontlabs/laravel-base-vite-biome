import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { AccountOption } from '@/types/budget';

type Props = {
    name: string;
    accounts: AccountOption[];
    value: string;
    onChange: (value: string) => void;
    placeholder?: string;
};

export function AccountSelect({
    name,
    accounts,
    value,
    onChange,
    placeholder = 'Select account',
}: Props) {
    return (
        <Select name={name} value={value} onValueChange={onChange}>
            <SelectTrigger id={name}>
                <SelectValue placeholder={placeholder} />
            </SelectTrigger>
            <SelectContent>
                {accounts.map((account) => (
                    <SelectItem key={account.id} value={String(account.id)}>
                        {account.name}
                    </SelectItem>
                ))}
            </SelectContent>
        </Select>
    );
}
