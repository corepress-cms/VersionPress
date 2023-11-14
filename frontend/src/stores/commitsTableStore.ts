/// <reference path='../interfaces/Visualisation.d.ts' />
/// <reference path='../components/common/Commits.d.ts' />

import { action, computed, observable, makeObservable } from 'mobx';
import differenceBy from 'lodash/differenceBy';

import appStore from './appStore';
import { indexOf } from '../utils/CommitUtils';
import { generateGraphData } from '../actions/utils';
import CommitRow from '../entities/CommitRow';

import navigationStore from './navigationStore';

class CommitsTableStore {
  commitRows: CommitRow[] = [];
  pages: number[] = [];
  showVisualisation: boolean = localStorage
    ? !!localStorage.getItem('showVisualization')
    : false;

  constructor() {
    makeObservable(this, {
      commitRows: observable,
      pages: observable,
      showVisualisation: observable,
      commits: computed,
      selectableCommits: computed,
      areAllCommitsSelected: computed,
      branches: computed,
      toggleShowVisualisation: action,
      setCommitRows: action,
      setPages: action,
      setSelectedCommits: action,
      reset: action
    });
  }

  get commits() {
    return this.commitRows.map(row => row.commit);
  }

  get selectableCommits() {
    return this.commits.filter((commit: Commit) => commit.canUndo);
  }

  get areAllCommitsSelected() {
    return this.commits.length > 0 &&
      !differenceBy(this.selectableCommits, appStore.selectedCommits, ((value: Commit) => value.hash)).length;
  }

  get branches() {
    let branches: number[] = [];

    this.commitRows.forEach(commitRow => {
      const { branch } = commitRow.visualisation!;
      if (branches.indexOf(branch) === -1) {
        branches.push(branch);
      }
    });

    return branches.length;
  }

  toggleShowVisualisation = (showVisualisation?: boolean) => {
    if (typeof showVisualisation !== 'boolean' && navigationStore.activeQuery !== '') {
      return;
    }
    this.showVisualisation = typeof showVisualisation === 'boolean'
      ? showVisualisation
      : !this.showVisualisation;

    if (localStorage) {
      localStorage.setItem('showVisualization', this.showVisualisation ? 'true' : '');
    }
  };

  setCommitRows = (commitRows: CommitRow[]) => {
    this.commitRows = commitRows;

    let graphStructure: CommitGraph[] = [];
    this.commits.forEach(commit => {
      graphStructure.push({
        sha: commit.hash,
        parents: commit.parentHashes,
        environment: commit.environment,
      });
    });

    const visualization = generateGraphData(graphStructure);

    let environments: { [key: string]: boolean } = {};
    this.commitRows.forEach((commitRow, i) => {
      let upper, lower;

      lower = i === this.commitRows.length - 1
        ? null
        : visualization[i];

      upper = i === 0
        ? null
        : visualization[i - 1];

      commitRow.setVisualisation({
        upperRoutes: upper ? upper.routes : [],
        lowerRoutes: lower ? lower.routes : [],
        environment: visualization[i].environment,
        branch: visualization[i].branch,
        offset: visualization[i].offset,
        isLastEnvCommit: !environments[visualization[i].environment],
      });

      if (!environments[visualization[i].environment]) {
        environments[visualization[i].environment] = true;
      }
    });
  };

  setPages = (pages: number[]) => {
    this.pages = pages;
  };

  setSelectedCommits = (selectedCommits: Commit[]) => {
    this.commitRows.forEach(commitRow => {
      commitRow.isSelected = indexOf(selectedCommits, commitRow.commit) !== -1;
    });
  };

  reset = () => {
    this.commitRows = [];
    this.pages = [];
  };
}

const commitsTableStore = new CommitsTableStore();

export { CommitsTableStore };
export default commitsTableStore;
