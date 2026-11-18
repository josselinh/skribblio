import type { Visibility } from '@/types/models';

export const visibilityLabels: Record<Visibility, string> = {
    public: 'Public',
    private: 'Privé',
};

export const visibilityDescriptions: Record<Visibility, string> = {
    public: 'Visible par tous les utilisateurs, qui peuvent aussi y ajouter des mots.',
    private: 'Visible par vous uniquement.',
};
