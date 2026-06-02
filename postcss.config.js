import tailwindcss from 'tailwindcss';
import autoprefixer from 'autoprefixer';

const removeMozColumnGap = {
    postcssPlugin: 'remove-moz-column-gap',
    OnceExit(root) {
        root.walkDecls('-moz-column-gap', (decl) => decl.remove());
    },
};

export default {
    plugins: [
        tailwindcss,
        autoprefixer,
        removeMozColumnGap,
    ],
};
