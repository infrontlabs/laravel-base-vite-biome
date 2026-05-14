import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { CategoryOption } from '@/types/budget';

type Props = {
    name: string;
    categories: CategoryOption[];
    value: string;
    onChange: (value: string) => void;
    allowNone?: boolean;
    placeholder?: string;
};

export function CategorySelect({
    name,
    categories,
    value,
    onChange,
    allowNone = true,
    placeholder = 'Select category',
}: Props) {
    return (
        <Select name={name} value={value} onValueChange={onChange}>
            <SelectTrigger id={name}>
                <SelectValue placeholder={placeholder} />
            </SelectTrigger>
            <SelectContent>
                {allowNone && <SelectItem value="none">No category</SelectItem>}
                {categories.map((category) => (
                    <SelectItem key={category.id} value={String(category.id)}>
                        {category.name}
                    </SelectItem>
                ))}
            </SelectContent>
        </Select>
    );
}
