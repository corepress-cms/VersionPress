/// <reference path='../common/Commits.d.ts' />

import * as React from 'react';
import classNames from 'classnames';
import {format} from 'date-fns';
import { parse } from '../search/modifiers/date/adapter';
import { inject, observer } from 'mobx-react';

import { undoCommits, rollbackToCommit, selectCommits } from '../../actions';
import Row from './row/Row';
import Footer from './footer/Footer';
import Header from './header/Header';
import Note from './note/Note';
import { revertDialog } from '../portal/portal';
import { findIndex } from '../../utils/ArrayUtils';

import CommitRow from '../../entities/CommitRow';
import { AppStore } from '../../stores/appStore';
import { CommitsTableStore } from '../../stores/commitsTableStore';
import { NavigationStore } from '../../stores/navigationStore';
import { LoadingStore } from '../../stores/loadingStore';

import './CommitsTable.less';

interface CommitsTableProps {
  appStore?: AppStore;
  commitsTableStore?: CommitsTableStore;
  navigationStore?: NavigationStore;
  loadingStore?: LoadingStore;
}

class CommitsTable extends React.Component<CommitsTableProps, {}> {

  onSelectAllChange = (isChecked: boolean) => {
    const { commitsTableStore } = this.props;

    this.onCommitsSelect(commitsTableStore!.commits, isChecked, false);
  }

  onUndo = (hash: string, message: string) => {
    const title = (
      <span>Undo <em>{message}</em>?</span>
    );

    revertDialog(title, () => undoCommits([hash]));
  }

  onRollback = (hash: string, date: string) => {
    const title = (
      <span>Roll back to <em>{format(parse(date), 'LLL')}</em>?</span>
    );

    revertDialog(title, () => rollbackToCommit(hash));
  }

  onCommitsSelect = (commitsToSelect: Commit[], isChecked: boolean, isShiftKey: boolean) => {
    selectCommits(commitsToSelect, isChecked, isShiftKey);
  }

  renderRow = (commitRow: CommitRow, displayNotAbleNote: boolean) => {
    const { appStore, commitsTableStore } = this.props;

    const row = (
      <Row
        commitRow={commitRow}
        enableActions={appStore!.enableActions}
        showVisualisation={commitsTableStore!.showVisualisation}
        onUndo={this.onUndo}
        onRollback={this.onRollback}
        onCommitsSelect={this.onCommitsSelect}
        onToggleShowVisualisation={commitsTableStore!.toggleShowVisualisation}
        key={commitRow.commit.hash}
      />
    );

    if (displayNotAbleNote) {
      return [
        <Note key='note'>
          VersionPress is not able to undo changes made before it has been activated.
        </Note>,
        row,
      ];
    }

    return [row];
  }

  render() {
    const { appStore, commitsTableStore, navigationStore, loadingStore } = this.props;
    const { enableActions } = appStore!;
    const {
      pages,
      commits,
      commitRows,
      showVisualisation,
      selectableCommits,
      areAllCommitsSelected,
      branches,
      toggleShowVisualisation,
    } = commitsTableStore!;
    const { isLoading } = loadingStore!;

    const commitsTableClassName = classNames({
      'vp-table': true,
      'widefat': true,
      'fixed': true,
      'loading': isLoading,
    });

    const notAbleNoteIndex = findIndex(commits, (commit: Commit, index: number) => (
      !commit.isEnabled && index < commits.length - 1
    ));

    return (
      <table className={commitsTableClassName}>
        <Header
          areAllCommitsSelected={areAllCommitsSelected}
          selectableCommitsCount={selectableCommits.length}
          enableActions={enableActions}
          canToggleVisualisation={navigationStore!.activeQuery === ''}
          showVisualisation={showVisualisation}
          branches={branches}
          onSelectAllChange={this.onSelectAllChange}
          onToggleShowVisualisation={toggleShowVisualisation}
        />
        {commitRows.map((commitRow: CommitRow, index: number) => (
          this.renderRow(commitRow, index === notAbleNoteIndex)
        ))}
        <Footer pages={pages} />
      </table>
    );
  }

}

export default inject('appStore', 'commitsTableStore', 'navigationStore', 'loadingStore')(observer(CommitsTable));
