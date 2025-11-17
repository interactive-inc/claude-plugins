#!/usr/bin/env python3
"""
Laravel Mail Scaffold Generator

このスクリプトは、Laravel メール関連のボイラープレートコードを自動生成します。

使用方法:
    # インタラクティブモード
    python3 generate_mail_scaffold.py

    # コマンドラインモード
    python3 generate_mail_scaffold.py --name JobofferApplied --model Application --recipient consumer

    # ヘルプ
    python3 generate_mail_scaffold.py --help
"""

import argparse
import os
import sys
from pathlib import Path
from typing import Dict, Optional


class MailScaffoldGenerator:
    """メール関連のスキャフォールドを生成するクラス"""

    RECIPIENT_TYPES = ['consumer', 'partner', 'administrator']

    def __init__(self, skill_dir: Path):
        """
        Args:
            skill_dir: スキルのルートディレクトリ
        """
        self.skill_dir = skill_dir
        self.templates_dir = skill_dir / 'assets' / 'templates'

    def generate(
        self,
        name: str,
        model: str,
        recipient: str,
        project_root: Optional[Path] = None,
        generate_notification: bool = True,
        generate_test: bool = True,
        generate_twig: bool = True,
        generate_virtual_resource: bool = True,
    ) -> None:
        """
        メール関連のファイルを生成する

        Args:
            name: Mailable クラス名（例: JobofferApplied）
            model: モデル名（例: Application）
            recipient: 受信者タイプ（consumer/partner/administrator）
            project_root: プロジェクトのルートディレクトリ（Noneの場合は出力のみ）
            generate_notification: Notification クラスを生成するか
            generate_test: テストクラスを生成するか
            generate_twig: Twig テンプレートを生成するか
            generate_virtual_resource: VirtualResource オーバーライドファイルを生成するか
        """
        if recipient not in self.RECIPIENT_TYPES:
            raise ValueError(f"recipient must be one of: {', '.join(self.RECIPIENT_TYPES)}")

        # プレースホルダーの置換マップ
        replacements = self._create_replacements(name, model, recipient)

        # Mailable クラスを生成
        mailable_content = self._generate_mailable(replacements)
        print(f"\n{'='*80}")
        print(f"Mailable Class: app/Mail/{name}.php")
        print(f"{'='*80}")
        print(mailable_content)

        if project_root:
            self._write_file(
                project_root / 'server' / 'app' / 'Mail' / f'{name}.php',
                mailable_content
            )

        # Notification クラスを生成
        if generate_notification:
            notification_name = f"{name}Notification"
            notification_content = self._generate_notification(replacements, notification_name)
            print(f"\n{'='*80}")
            print(f"Notification Class: app/Notifications/{notification_name}.php")
            print(f"{'='*80}")
            print(notification_content)

            if project_root:
                self._write_file(
                    project_root / 'server' / 'app' / 'Notifications' / f'{notification_name}.php',
                    notification_content
                )

        # Test クラスを生成
        if generate_test:
            test_name = f"{name}Test"
            test_content = self._generate_test(replacements, test_name)
            print(f"\n{'='*80}")
            print(f"Test Class: tests/Feature/Mail/{test_name}.php")
            print(f"{'='*80}")
            print(test_content)

            if project_root:
                self._write_file(
                    project_root / 'server' / 'tests' / 'Feature' / 'Mail' / f'{test_name}.php',
                    test_content
                )

        # Twig テンプレートを生成
        if generate_twig:
            template_name = replacements['template_name']
            recipient_dir = f"to_{recipient}"

            # HTML テンプレート
            html_content = self._generate_twig_html(replacements)
            print(f"\n{'='*80}")
            print(f"HTML Template: resources/views/emails/{recipient_dir}/{template_name}.twig")
            print(f"{'='*80}")
            print(html_content)

            # Plain Text テンプレート
            text_content = self._generate_twig_text(replacements)
            print(f"\n{'='*80}")
            print(f"Text Template: resources/views/emails/{recipient_dir}/{template_name}_plain.twig")
            print(f"{'='*80}")
            print(text_content)

            if project_root:
                twig_dir = project_root / 'server' / 'resources' / 'views' / 'emails' / recipient_dir
                self._write_file(twig_dir / f'{template_name}.twig', html_content)
                self._write_file(twig_dir / f'{template_name}_plain.twig', text_content)

        # VirtualResource オーバーライドファイルを生成
        if generate_virtual_resource:
            template_name = replacements['template_name']
            recipient_dir = f"to_{recipient}"

            # HTML版オーバーライド
            vr_html_content = self._generate_virtual_resource_html(replacements)
            print(f"\n{'='*80}")
            print(f"VirtualResource HTML: database/seeders/data/virtual_resources/views/emails/{recipient_dir}/{template_name}.twig")
            print(f"{'='*80}")
            print(vr_html_content)

            # Plain Text版オーバーライド
            vr_text_content = self._generate_virtual_resource_text(replacements)
            print(f"\n{'='*80}")
            print(f"VirtualResource Text: database/seeders/data/virtual_resources/views/emails/{recipient_dir}/{template_name}_plain.twig")
            print(f"{'='*80}")
            print(vr_text_content)

            if project_root:
                vr_dir = project_root / 'server' / 'database' / 'seeders' / 'data' / 'virtual_resources' / 'views' / 'emails' / recipient_dir
                self._write_file(vr_dir / f'{template_name}.twig', vr_html_content)
                self._write_file(vr_dir / f'{template_name}_plain.twig', vr_text_content)

    def _create_replacements(self, name: str, model: str, recipient: str) -> Dict[str, str]:
        """プレースホルダーの置換マップを作成"""
        # キャメルケースをスネークケースに変換
        template_name = self._camel_to_snake(name)

        return {
            'mailable_name': name,
            'notification_name': f'{name}Notification',
            'model_name': model,
            'model_var': model[0].lower() + model[1:],  # Application -> application
            'recipient_type': recipient,
            'recipient_dir': f'to_{recipient}',
            'template_name': template_name,
        }

    def _camel_to_snake(self, name: str) -> str:
        """キャメルケースをスネークケースに変換"""
        import re
        s1 = re.sub('(.)([A-Z][a-z]+)', r'\1_\2', name)
        return re.sub('([a-z0-9])([A-Z])', r'\1_\2', s1).lower()

    def _generate_mailable(self, r: Dict[str, str]) -> str:
        """Mailable クラスを生成"""
        template_path = self.templates_dir / 'mailable-template.php'
        content = template_path.read_text()

        content = content.replace('YourMailableName', r['mailable_name'])
        content = content.replace('YourModel', r['model_name'])
        content = content.replace('$model', f"${r['model_var']}")
        content = content.replace('to_consumer', r['recipient_dir'])
        content = content.replace('your_template', r['template_name'])

        return content

    def _generate_notification(self, r: Dict[str, str], notification_name: str) -> str:
        """Notification クラスを生成"""
        template_path = self.templates_dir / 'notification-template.php'
        content = template_path.read_text()

        content = content.replace('YourNotificationName', notification_name)
        content = content.replace('YourMailableName', r['mailable_name'])
        content = content.replace('YourModel', r['model_name'])
        content = content.replace('$model', f"${r['model_var']}")

        return content

    def _generate_test(self, r: Dict[str, str], test_name: str) -> str:
        """Test クラスを生成"""
        template_path = self.templates_dir / 'mail-test-template.php'
        content = template_path.read_text()

        content = content.replace('YourMailableNameTest', test_name)
        content = content.replace('YourMailableName', r['mailable_name'])
        content = content.replace('YourModel', r['model_name'])
        content = content.replace('seedModel', f"seed{r['model_name']}")
        content = content.replace('to_consumer', r['recipient_dir'])
        content = content.replace('your_template', r['template_name'])

        return content

    def _generate_twig_html(self, r: Dict[str, str]) -> str:
        """HTML Twig テンプレートを生成"""
        template_path = self.templates_dir / 'twig' / 'email-template.twig'
        content = template_path.read_text()

        # 基本的な置換（必要に応じてカスタマイズ）
        return content

    def _generate_twig_text(self, r: Dict[str, str]) -> str:
        """Plain Text Twig テンプレートを生成"""
        template_path = self.templates_dir / 'twig' / 'email-template_plain.twig'
        content = template_path.read_text()

        # 基本的な置換（必要に応じてカスタマイズ）
        return content

    def _generate_virtual_resource_html(self, r: Dict[str, str]) -> str:
        """VirtualResource HTML オーバーライドファイルを生成"""
        template_path = self.templates_dir / 'twig' / 'virtual_resource_override.twig'
        content = template_path.read_text()

        # プレースホルダーを置換
        content = content.replace('to_consumer', r['recipient_dir'])
        content = content.replace('your_template', r['template_name'])

        return content

    def _generate_virtual_resource_text(self, r: Dict[str, str]) -> str:
        """VirtualResource Plain Text オーバーライドファイルを生成"""
        template_path = self.templates_dir / 'twig' / 'virtual_resource_override_plain.twig'
        content = template_path.read_text()

        # プレースホルダーを置換
        content = content.replace('to_consumer', r['recipient_dir'])
        content = content.replace('your_template', r['template_name'])

        return content

    def _write_file(self, path: Path, content: str) -> None:
        """ファイルを書き込む"""
        # ディレクトリが存在しない場合は作成
        path.parent.mkdir(parents=True, exist_ok=True)

        # ファイルが既に存在する場合は確認
        if path.exists():
            response = input(f"\n{path} は既に存在します。上書きしますか？ (y/N): ")
            if response.lower() != 'y':
                print(f"スキップしました: {path}")
                return

        # ファイルを書き込む
        path.write_text(content)
        print(f"✓ 作成しました: {path}")


def interactive_mode(generator: MailScaffoldGenerator) -> None:
    """インタラクティブモードで情報を収集"""
    print("=" * 80)
    print("Laravel Mail Scaffold Generator - インタラクティブモード")
    print("=" * 80)

    # Mailable 名を入力
    name = input("\nMailable クラス名を入力してください (例: JobofferApplied): ").strip()
    if not name:
        print("エラー: Mailable 名は必須です")
        sys.exit(1)

    # Model 名を入力
    model = input("モデル名を入力してください (例: Application): ").strip()
    if not model:
        print("エラー: モデル名は必須です")
        sys.exit(1)

    # Recipient タイプを選択
    print(f"\n受信者タイプを選択してください:")
    for i, recipient_type in enumerate(MailScaffoldGenerator.RECIPIENT_TYPES, 1):
        print(f"  {i}. {recipient_type}")

    choice = input("選択 (1-3): ").strip()
    try:
        recipient_idx = int(choice) - 1
        recipient = MailScaffoldGenerator.RECIPIENT_TYPES[recipient_idx]
    except (ValueError, IndexError):
        print("エラー: 無効な選択です")
        sys.exit(1)

    # オプションの確認
    generate_notification = input("\nNotification クラスも生成しますか？ (Y/n): ").strip().lower() != 'n'
    generate_test = input("Test クラスも生成しますか？ (Y/n): ").strip().lower() != 'n'
    generate_twig = input("Twig テンプレートも生成しますか？ (Y/n): ").strip().lower() != 'n'
    generate_virtual_resource = input("VirtualResource オーバーライドファイルも生成しますか？ (Y/n): ").strip().lower() != 'n'

    # プロジェクトルートの確認
    write_files = input("\nファイルをプロジェクトに書き込みますか？ (Y/n): ").strip().lower() != 'n'
    project_root = None
    if write_files:
        project_root_str = input("プロジェクトのルートディレクトリを入力してください: ").strip()
        project_root = Path(project_root_str) if project_root_str else None

    # 生成実行
    generator.generate(
        name=name,
        model=model,
        recipient=recipient,
        project_root=project_root,
        generate_notification=generate_notification,
        generate_test=generate_test,
        generate_twig=generate_twig,
        generate_virtual_resource=generate_virtual_resource,
    )

    print("\n" + "=" * 80)
    print("✓ 生成が完了しました！")
    print("=" * 80)


def main():
    parser = argparse.ArgumentParser(
        description='Laravel メール関連のスキャフォールドを生成します',
        formatter_class=argparse.RawDescriptionHelpFormatter,
        epilog="""
使用例:
  # インタラクティブモード
  python3 generate_mail_scaffold.py

  # コマンドラインモード
  python3 generate_mail_scaffold.py --name JobofferApplied --model Application --recipient consumer

  # Notification を生成せずに Mailable とテストのみ生成
  python3 generate_mail_scaffold.py --name JobofferApplied --model Application --recipient consumer --no-notification

  # ファイルをプロジェクトに書き込む
  python3 generate_mail_scaffold.py --name JobofferApplied --model Application --recipient consumer --project-root /path/to/project
        """
    )

    parser.add_argument('--name', help='Mailable クラス名 (例: JobofferApplied)')
    parser.add_argument('--model', help='モデル名 (例: Application)')
    parser.add_argument('--recipient', choices=MailScaffoldGenerator.RECIPIENT_TYPES,
                        help='受信者タイプ')
    parser.add_argument('--project-root', type=Path,
                        help='プロジェクトのルートディレクトリ（ファイルを書き込む場合）')
    parser.add_argument('--no-notification', action='store_true',
                        help='Notification クラスを生成しない')
    parser.add_argument('--no-test', action='store_true',
                        help='Test クラスを生成しない')
    parser.add_argument('--no-twig', action='store_true',
                        help='Twig テンプレートを生成しない')
    parser.add_argument('--no-virtual-resource', action='store_true',
                        help='VirtualResource オーバーライドファイルを生成しない')

    args = parser.parse_args()

    # スキルディレクトリを取得
    skill_dir = Path(__file__).parent.parent
    generator = MailScaffoldGenerator(skill_dir)

    # コマンドライン引数が指定されていない場合はインタラクティブモード
    if not args.name or not args.model or not args.recipient:
        interactive_mode(generator)
    else:
        # コマンドラインモード
        generator.generate(
            name=args.name,
            model=args.model,
            recipient=args.recipient,
            project_root=args.project_root,
            generate_notification=not args.no_notification,
            generate_test=not args.no_test,
            generate_twig=not args.no_twig,
            generate_virtual_resource=not args.no_virtual_resource,
        )

        print("\n" + "=" * 80)
        print("✓ 生成が完了しました！")
        print("=" * 80)


if __name__ == '__main__':
    main()
