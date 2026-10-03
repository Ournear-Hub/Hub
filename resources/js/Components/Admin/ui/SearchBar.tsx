import Input from '@/Components/Admin/ui/Input';

type SearchBarProps = {
    name?: string;
    defaultValue?: string;
    placeholder?: string;
    disabled?: boolean;
    title?: string;
};

export default function SearchBar({
    name = 'search',
    defaultValue,
    placeholder = 'Search',
    disabled = false,
    title,
}: SearchBarProps) {
    return (
        <Input
            type="search"
            name={name}
            defaultValue={defaultValue}
            placeholder={placeholder}
            disabled={disabled}
            title={title}
            aria-label={placeholder}
        />
    );
}
