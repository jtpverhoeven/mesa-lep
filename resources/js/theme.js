import { definePreset } from '@openvue/themes';
import Aura from '@openvue/themes/aura';

const pastelButton = (color) => ({
    background: `color-mix(in srgb, ${color} 14%, var(--surface))`,
    hoverBackground: `color-mix(in srgb, ${color} 22%, var(--surface))`,
    activeBackground: `color-mix(in srgb, ${color} 30%, var(--surface))`,
    borderColor: `color-mix(in srgb, ${color} 40%, var(--control-border))`,
    hoverBorderColor: color,
    activeBorderColor: color,
    color: `color-mix(in srgb, ${color} 45%, var(--ink))`,
    hoverColor: `color-mix(in srgb, ${color} 45%, var(--ink))`,
    activeColor: `color-mix(in srgb, ${color} 45%, var(--ink))`,
    focusRing: { color, shadow: 'none' },
});

const pastelButtons = {
    info: pastelButton('{sky.500}'),
    warn: pastelButton('{amber.500}'),
    success: pastelButton('{green.500}'),
};

const secondaryButton = {
    background: 'var(--surface)',
    hoverBackground: 'var(--surface-alt)',
    activeBackground: 'var(--accent-faint)',
    borderColor: 'var(--control-border)',
    hoverBorderColor: 'var(--accent)',
    activeBorderColor: 'var(--accent)',
    color: 'var(--ink)',
    hoverColor: 'var(--ink)',
    activeColor: 'var(--ink)',
    focusRing: { color: 'var(--accent)', shadow: 'none' },
};

const colorScheme = {
    primary: {
        color: 'var(--accent)',
        contrastColor: 'var(--accent-contrast)',
        hoverColor: 'color-mix(in srgb, var(--accent), var(--ink) 12%)',
        activeColor: 'color-mix(in srgb, var(--accent), var(--ink) 20%)',
    },
    highlight: {
        background: 'var(--accent-faint)',
        focusBackground: 'var(--accent-faint)',
        color: 'var(--accent)',
        focusColor: 'var(--accent)',
    },
    text: {
        color: 'var(--ink)',
        hoverColor: 'var(--ink)',
        mutedColor: 'var(--muted)',
        hoverMutedColor: 'var(--ink)',
    },
    content: {
        background: 'var(--surface)',
        hoverBackground: 'var(--surface-alt)',
        borderColor: 'var(--line)',
        color: 'var(--ink)',
        hoverColor: 'var(--ink)',
    },
    formField: {
        background: 'var(--surface)',
        disabledBackground: 'var(--surface-alt)',
        filledBackground: 'var(--surface-alt)',
        filledHoverBackground: 'var(--surface-alt)',
        filledFocusBackground: 'var(--field-focus)',
        borderColor: 'var(--control-border)',
        hoverBorderColor: 'var(--accent)',
        focusBorderColor: 'var(--accent)',
        invalidBorderColor: 'var(--danger)',
        color: 'var(--ink)',
        disabledColor: 'var(--muted)',
        placeholderColor: 'var(--muted)',
        invalidPlaceholderColor: 'var(--danger)',
        iconColor: 'var(--muted)',
        shadow: 'none',
    },
    list: {
        option: {
            focusBackground: 'var(--surface-alt)',
            selectedBackground: 'var(--accent-faint)',
            selectedFocusBackground: 'var(--accent-faint)',
            color: 'var(--ink)',
            focusColor: 'var(--ink)',
            selectedColor: 'var(--accent)',
            selectedFocusColor: 'var(--accent)',
        },
    },
    overlay: {
        select: { background: 'var(--surface)', borderColor: 'var(--control-border)', color: 'var(--ink)' },
        popover: { background: 'var(--surface)', borderColor: 'var(--control-border)', color: 'var(--ink)' },
        modal: { background: 'var(--surface)', borderColor: 'var(--control-border)', color: 'var(--ink)' },
    },
};

export const MesaPreset = definePreset(Aura, {
    primitive: {
        borderRadius: { none: '0', xs: '2px', sm: '2px', md: '2px', lg: '4px', xl: '6px' },
    },
    semantic: {
        primary: {
            50: 'color-mix(in srgb, var(--accent) 8%, white)',
            100: 'color-mix(in srgb, var(--accent) 16%, white)',
            200: 'color-mix(in srgb, var(--accent) 32%, white)',
            300: 'color-mix(in srgb, var(--accent) 50%, white)',
            400: 'color-mix(in srgb, var(--accent) 75%, white)',
            500: 'var(--accent)',
            600: 'color-mix(in srgb, var(--accent) 88%, black)',
            700: 'color-mix(in srgb, var(--accent) 76%, black)',
            800: 'color-mix(in srgb, var(--accent) 64%, black)',
            900: 'color-mix(in srgb, var(--accent) 52%, black)',
            950: 'color-mix(in srgb, var(--accent) 40%, black)',
        },
        focusRing: { width: '2px', style: 'solid', color: 'var(--accent)', offset: '2px', shadow: 'none' },
        formField: {
            paddingX: '10px',
            paddingY: '8px',
            borderRadius: 'var(--control-radius)',
            focusRing: { width: '0', style: 'none', shadow: 'none' },
            sm: { fontSize: '12px', paddingX: '8px', paddingY: '6px' },
            lg: { fontSize: '14px', paddingX: '12px', paddingY: '10px' },
        },
        list: { padding: '0', gap: '0', option: { padding: '8px 10px', borderRadius: '0' } },
        overlay: {
            select: { borderRadius: 'var(--control-radius)' },
            popover: { borderRadius: 'var(--control-radius)' },
            modal: { borderRadius: 'var(--control-radius)', padding: '14px' },
        },
        colorScheme: { light: colorScheme, dark: colorScheme },
    },
    components: {
        datatable: {
            headerCell: { background: 'var(--surface-header)', color: 'var(--muted)', padding: '12px 14px' },
            bodyCell: { padding: '12px 14px' },
        },
        button: {
            root: { paddingX: '12px', paddingY: '6px', iconOnlyWidth: '34px', label: { fontWeight: '600' } },
            colorScheme: {
                light: { root: { secondary: secondaryButton, ...pastelButtons } },
                dark: { root: { secondary: secondaryButton, ...pastelButtons } },
            },
        },
        paginator: {
            root: { padding: '8px 12px', gap: '4px', borderRadius: '0' },
            navButton: { width: '30px', height: '30px', borderRadius: 'var(--control-radius)' },
            colorScheme: {
                light: { root: { background: 'var(--surface-alt)', color: 'var(--muted)' }, navButton: { selectedBackground: 'var(--accent)', selectedColor: 'var(--accent-contrast)' } },
                dark: { root: { background: 'var(--surface-alt)', color: 'var(--muted)' }, navButton: { selectedBackground: 'var(--accent)', selectedColor: 'var(--accent-contrast)' } },
            },
        },
        tabs: {
            tab: { padding: '9px 10px', activeBackground: 'var(--accent-faint)' },
            tabpanel: { padding: '12px' },
            activeBar: { height: '2px' },
        },
        dialog: {
            header: { padding: '9px 12px' },
            title: { fontSize: '15px', fontWeight: '600' },
            content: { padding: '14px' },
            footer: { padding: '10px 12px', gap: '8px' },
        },
    },
});

export default {
    pt: {
        datatable: {
            table: { class: 'data-table' },
        },
        paginator: {
            root: { class: 'border-t border-line' },
            content: { class: 'w-full justify-end' },
        },
    },
    theme: {
        preset: MesaPreset,
        options: {
            darkModeSelector: '[data-theme="dark"]',
            cssLayer: { name: 'openvue', order: 'theme, base, openvue, components, utilities' },
        },
    },
};