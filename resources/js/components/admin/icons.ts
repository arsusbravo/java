import {
    Compass,
    Database,
    Handshake,
    Hotel,
    Image,
    Map,
    MapPin,
    MousePointerClick,
    Newspaper,
    Shapes,
    Star,
    Tag,
    Users,
    Utensils,
    type LucideIcon,
} from 'lucide-vue-next';

// Icons referenced by name from App\Admin\Resource::$icon
const icons: Record<string, LucideIcon> = {
    Compass,
    Handshake,
    Hotel,
    Image,
    Map,
    MapPin,
    MousePointerClick,
    Newspaper,
    Shapes,
    Star,
    Tag,
    Users,
    Utensils,
};

export function resourceIcon(name: string): LucideIcon {
    return icons[name] ?? Database;
}
