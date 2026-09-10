import { defineStore } from 'pinia';
import { legacyFieldValues } from './legacyFieldDefaults';

let activeProjectsRequest;
let activeProjectRequest;

export const useProjectSelectorStore = defineStore('sampleProjectSelector', {
    state: () => ({
        projectsEndpoint: '', projectEndpoint: '', fieldDefinitions: [], projects: [], selectedId: '',
        form: { project_name: '', custom_fields: {} }, lockedFields: [], loadingProjects: false, loadingProject: false, error: '',
    }),
    actions: {
        configure(projectsEndpoint, projectEndpoint) {
            this.projectsEndpoint = projectsEndpoint;
            this.projectEndpoint = projectEndpoint;
        },
        setFieldDefinitions(fields) {
            this.fieldDefinitions = fields;
            this.resetForm();
        },
        resetForm() {
            const previousValues = this.form.custom_fields;
            const defaults = legacyFieldValues(this.fieldDefinitions);
            this.form = {
                project_name: '',
                custom_fields: Object.fromEntries(Object.entries(defaults).map(([name, value]) => [
                    name,
                    this.lockedFields.includes(name) ? previousValues[name] ?? value : value,
                ])),
            };
        },
        toggleFieldLock(name) {
            this.lockedFields = this.lockedFields.includes(name)
                ? this.lockedFields.filter((field) => field !== name)
                : [...this.lockedFields, name];
        },
        async loadForClient(clientId) {
            activeProjectsRequest?.abort();
            activeProjectRequest?.abort();
            this.projects = [];
            this.selectedId = '';
            this.resetForm();
            this.error = '';
            if (!clientId) {
                this.loadingProjects = false;
                this.loadingProject = false;
                return;
            }

            const request = new AbortController();
            activeProjectsRequest = request;
            this.loadingProjects = true;
            try {
                const response = await fetch(this.projectsEndpoint.replace('__CLIENT__', clientId), {
                    headers: { Accept: 'application/json' },
                    signal: request.signal,
                });
                if (!response.ok) throw new Error();
                this.projects = (await response.json()).data;
            } catch (error) {
                if (error.name !== 'AbortError') this.error = 'Projecten konden niet worden opgehaald.';
            } finally {
                if (activeProjectsRequest === request) this.loadingProjects = false;
            }
        },
        async selectProject(projectId, clientId) {
            activeProjectRequest?.abort();
            this.selectedId = projectId;
            this.resetForm();
            this.error = '';
            if (!projectId) {
                this.loadingProject = false;
                return;
            }

            const request = new AbortController();
            activeProjectRequest = request;
            this.loadingProject = true;
            try {
                const response = await fetch(this.projectEndpoint.replace('__PROJECT__', projectId), {
                    headers: { Accept: 'application/json' },
                    signal: request.signal,
                });
                if (!response.ok) throw new Error();
                const project = (await response.json()).data;
                if (String(project.client) !== String(clientId)) throw new Error();
                this.form.project_name = project.project_name ?? '';
                this.form.custom_fields = legacyFieldValues(this.fieldDefinitions, project.custom_fields);
            } catch (error) {
                if (error.name !== 'AbortError') {
                    this.selectedId = '';
                    this.resetForm();
                    this.error = 'Projectgegevens konden niet worden opgehaald.';
                }
            } finally {
                if (activeProjectRequest === request) this.loadingProject = false;
            }
        },
    },
});